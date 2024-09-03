<?php

namespace App\Console\Commands;

use App\Jobs\GenerateIconEmbedding;
use App\Models\Icon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FetchAndProcessIcons extends Command
{
    protected $signature = 'icons:fetch-and-process {--all : Process all icons} {--json-only : Only fetch and save JSON without processing icons}';

    protected $description = 'Fetch and process icons from Phosphor Icons repository';

    private $iconCounter = 0;

    public function handle()
    {
        $this->createJsonFileFromMetadata();

        if ($this->option('json-only')) {
            return;
        }

        $this->downloadAndExtractRepo();

        $this->processIcons();

        $this->cleanup();

        $this->info('Icons fetched and processed successfully.');
    }

    private function createJsonFileFromMetadata()
    {
        $iconMetadataFileURL = 'https://raw.githubusercontent.com/phosphor-icons/core/main/src/icons.ts';
        $response = Http::get($iconMetadataFileURL);

        if ($response->successful()) {
            $content = $response->body();
            $jsonContent = $this->convertTypescriptToJson($content);
            if ($jsonContent !== null) {
                Storage::put('icons/metadata.json', $jsonContent);
                $this->info('Successfully created metadata.json file.');
            } else {
                $this->error('Failed to create metadata.json file.');
            }
        } else {
            $this->error('Failed to read metadata file from repo.');
        }
    }

    private function downloadAndExtractRepo()
    {
        $zipUrl = 'https://github.com/phosphor-icons/core/archive/refs/heads/main.zip';
        $zipPath = storage_path('app/icons/repo.zip');
        $extractPath = storage_path('app/icons/temp');

        $this->info('Downloading repository...');
        file_put_contents($zipPath, file_get_contents($zipUrl));

        $this->info('Extracting repository...');
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info('Repository extracted successfully.');
        } else {
            $this->error('Failed to extract repository.');
        }
    }

    private function processIcons()
    {
        $variantDirectories = [
            'regular' => 'core-main/assets/regular',
            'bold' => 'core-main/assets/bold',
            'duotone' => 'core-main/assets/duotone',
            'fill' => 'core-main/assets/fill',
            'light' => 'core-main/assets/light',
            'thin' => 'core-main/assets/thin',
        ];

        $metadata = json_decode(Storage::get('icons/metadata.json'), true);

        $this->info('Processing icons...');
        $bar = $this->output->createProgressBar(count($metadata) * count($variantDirectories));
        $bar->start();

        $processedIcons = [];

        foreach ($metadata as $iconData) {
            $iconName = $iconData['name'];
            $tags = $iconData['tags'];

            $icon = Icon::firstOrCreate([
                'name' => $iconName,
                'keywords' => json_encode($tags),
            ]);

            foreach ($variantDirectories as $variantType => $directory) {
                $filename = $variantType === 'regular' ? "{$iconName}.svg" : "{$iconName}-{$variantType}.svg";
                $sourcePath = storage_path("app/icons/temp/{$directory}/{$filename}");

                if (file_exists($sourcePath)) {
                    $this->processIconVariant($icon, $filename, $sourcePath, $variantType);
                }

                $bar->advance();
            }

            if (! in_array($icon->id, $processedIcons)) {
                GenerateIconEmbedding::dispatch($icon);
                $processedIcons[] = $icon->id;
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function processIconVariant($icon, $filename, $sourcePath, $variantType)
    {
        $destinationPath = "icons/{$variantType}/{$filename}";

        $svgContent = file_get_contents($sourcePath);
        $processedSvg = $this->processSvg($svgContent);

        $variant = $icon->variants()->firstOrCreate(
            ['type' => $variantType],
            ['file_path' => $destinationPath]
        );

        if (! $this->iconNeedsProcessing($variant, $processedSvg, $destinationPath)) {
            return;
        }

        Storage::put($destinationPath, $processedSvg);

        $variant->update(['file_path' => $destinationPath]);
    }

    private function processSvg($svgContent)
    {
        return preg_replace('/fill="[^"]+"/', 'fill="currentColor"', $svgContent);
    }

    private function iconNeedsProcessing($variant, $processedSvg, $destinationPath)
    {
        if (! $variant->file_path || ! Storage::exists($variant->file_path)) {
            return true;
        }

        $sourceHash = md5($processedSvg);
        $destinationHash = md5(Storage::get($destinationPath));

        return $sourceHash !== $destinationHash;
    }

    private function cleanup()
    {
        $this->info('Cleaning up temporary files...');
        Storage::deleteDirectory('icons/temp');
        Storage::delete('icons/repo.zip');
    }

    private function convertTypescriptToJson($typescriptContent)
    {
        // Extract the icons array
        preg_match('/export const icons = \(<const>\[(.*?)\]\) satisfies readonly IconEntry\[\];/s', $typescriptContent, $matches);

        if (empty($matches[1])) {
            return json_encode(['error' => 'Icons not found in the file'], JSON_PRETTY_PRINT);
        }

        $iconsString = $matches[1];

        // Split the icons string into individual icon objects
        $iconObjects = preg_split('/},\s*{/', $iconsString);

        $transformedIcons = [];

        foreach ($iconObjects as $index => $iconObject) {
            // Add opening brace if it's not the first object
            if ($index !== 0) {
                $iconObject = '{'.$iconObject;
            }
            // Add closing brace if it's not the last object
            if ($index !== count($iconObjects) - 1) {
                $iconObject .= '}';
            }

            // Extract name and tags
            preg_match('/name: "(.*?)"/', $iconObject, $nameMatch);
            preg_match('/tags: \[([\s\S]*?)\]/', $iconObject, $tagsMatch);

            if (! empty($nameMatch[1]) && ! empty($tagsMatch[1])) {
                $name = $nameMatch[1];
                $tags = preg_split('/,\s*/', $tagsMatch[1]);
                $tags = array_map(function ($tag) {
                    return trim($tag, " \t\n\r\0\x0B\"");
                }, $tags);

                $transformedIcons[] = [
                    'name' => $name,
                    'tags' => array_values(array_filter($tags)), // Remove empty elements
                ];
            }
        }

        // Convert to JSON
        $json = json_encode($transformedIcons, JSON_PRETTY_PRINT);
        $json = json_decode($json, true);
        foreach ($json as &$icon) {
            $icon['tags'] = array_values(array_filter($icon['tags'], function ($tag) {
                return ! str_starts_with($tag, '*') && ! str_ends_with($tag, '*');
            }));
        }
        $json = json_encode($json, JSON_PRETTY_PRINT);

        return $json;
    }
}
