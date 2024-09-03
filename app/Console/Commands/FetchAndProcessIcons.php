<?php

namespace App\Console\Commands;

use App\Jobs\GenerateIconEmbedding;
use App\Models\Icon;
use App\Models\Variant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchAndProcessIcons extends Command
{
    protected $signature = 'icons:fetch-and-process {--all : Process all icons} {--json-only : Only fetch and save JSON without processing icons}';

    protected $description = 'Fetch and process icons from Phosphor Icons repository';

    private $iconCounter = 0;

    public function handle()
    {
        $iconMetadataFileURL = 'https://raw.githubusercontent.com/phosphor-icons/core/main/src/icons.ts';
        $this->saveIconMetadataFile($iconMetadataFileURL);

        if ($this->option('json-only')) {
            return;
        }

        $baseUrl = 'https://api.github.com/repos/phosphor-icons/core/contents/assets';
        $response = Http::get($baseUrl);

        if ($response->successful()) {
            $directories = $response->json();
            $totalIcons = $this->countTotalIcons($directories);

            $bar = $this->output->createProgressBar($totalIcons);
            $bar->start();

            foreach ($directories as $directory) {
                if ($directory['type'] === 'dir') {
                    $this->processDirectory($directory['name'], $directory['url'], $bar);
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info('Icons fetched and jobs queued for processing.');
            $this->info('To process the jobs:');
            $this->info('1. Ensure your queue worker is running: php artisan queue:work');
            $this->info('2. Or, to process all queued jobs once: php artisan queue:work --stop-when-empty');
        } else {
            $statusCode = $response->status();
            $errorBody = $response->body();
            $this->error("Failed to fetch icons from the repository. Status code: $statusCode");
            $this->error("Error message: $errorBody");

            // Optionally, log the error for future reference
            Log::error("GitHub API Error: Status $statusCode, Body: $errorBody");
        }
    }

    private function countTotalIcons($directories)
    {
        $total = 0;
        foreach ($directories as $directory) {
            if ($directory['type'] === 'dir') {
                $response = Http::get($directory['url']);
                if ($response->successful()) {
                    $total += count($response->json());
                }
            }
        }

        return $total;
    }

    private function processDirectory($variantType, $url, $bar)
    {
        $response = Http::get($url);
        if ($response->successful()) {
            $files = $response->json();
            foreach ($files as $file) {
                if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'svg') {
                    $this->iconCounter++;
                    if ($this->shouldProcessIcon()) {
                        $this->processIcon($file['name'], $file['download_url'], $variantType);
                    }
                    $bar->advance();
                }
            }
        }
    }

    private function shouldProcessIcon()
    {
        if ($this->option('all') || ! app()->environment('local')) {
            return true;
        }

        return $this->iconCounter % 10 === 0;
    }

    private function processIcon($filename, $url, $variantType)
    {
        $fullIconName = pathinfo($filename, PATHINFO_FILENAME);

        // Extract the base icon name without the variant
        $iconName = $variantType === 'regular'
            ? $fullIconName
            : preg_replace("/-$variantType$/", '', $fullIconName);

        $svgContent = Http::get($url)->body();

        // Replace fill colors with currentColor
        $processedSvg = preg_replace('/fill="[^"]+"/', 'fill="currentColor"', $svgContent);

        // Save to file system
        $filePath = "icons/{$variantType}/{$filename}";
        Storage::put($filePath, $processedSvg);

        // Save to database
        $icon = Icon::firstOrCreate(['name' => $iconName]);
        $icon->variants()->updateOrCreate(
            ['type' => $variantType],
            ['file_path' => $filePath]
        );

        // Dispatch job to generate embedding
        GenerateIconEmbedding::dispatch($icon);
    }

    private function saveIconMetadataFile($url)
    {
        $response = Http::get($url);
        if ($response->successful()) {
            $content = $response->body();
            Storage::put('icons/metadata.ts', $content);
            $this->createJsonFileFromMetadata();
        }
    }

    private function createJsonFileFromMetadata()
    {
        $metadata = Storage::get('icons/metadata.ts');
        if ($metadata === null) {
            $this->error('Failed to read metadata.ts file.');

            return;
        }
        $jsonContent = $this->convertTypescriptToJson($metadata);
        if ($jsonContent !== null) {
            Storage::put('icons/metadata.json', $jsonContent);
            $this->info('Successfully created metadata.json file.');
            Storage::delete('icons/metadata.ts');
            $this->info('Successfully deleted metadata.ts file.');
        } else {
            $this->error('Failed to create metadata.json file.');
        }
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
