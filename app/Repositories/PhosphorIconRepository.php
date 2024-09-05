<?php

namespace App\Repositories;

use App\Contracts\IconRepositoryInterface;
use App\Jobs\GenerateIconEmbedding;
use App\Models\Icon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PhosphorIconRepository implements IconRepositoryInterface
{
    private const ICON_SET_NAME = 'phosphor';

    private const METADATA_URL = 'https://raw.githubusercontent.com/phosphor-icons/core/main/src/icons.ts';

    private const REPO_ZIP_URL = 'https://github.com/phosphor-icons/core/archive/refs/heads/main.zip';

    public function fetchMetadata(): array
    {
        $response = Http::get(self::METADATA_URL);

        if (! $response->successful()) {
            throw new \Exception('Failed to read metadata file from repo.');
        }

        $content = $response->body();
        $jsonContent = $this->convertTypescriptToJson($content);

        if (! is_array($jsonContent)) {
            throw new \Exception('Failed to convert metadata to JSON.');
        }

        return $jsonContent;
    }

    public function downloadAndExtractRepo(): void
    {
        $zipPath = storage_path('app/temp_icons/'.self::ICON_SET_NAME.'/repo.zip');
        $extractPath = storage_path('app/temp_icons/'.self::ICON_SET_NAME.'/temp');

        file_put_contents($zipPath, file_get_contents(self::REPO_ZIP_URL));

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            throw new \Exception('Failed to extract repository.');
        }
    }

    public function getIconVariants(): array
    {
        return [
            'regular' => 'core-main/assets/regular',
            'bold' => 'core-main/assets/bold',
            'duotone' => 'core-main/assets/duotone',
            'fill' => 'core-main/assets/fill',
            'light' => 'core-main/assets/light',
            'thin' => 'core-main/assets/thin',
        ];
    }

    public function processIcon(array $iconData): void
    {
        $icon = Icon::firstOrCreate([
            'name' => $iconData['name'],
            'keywords' => json_encode($iconData['tags']),
        ]);

        foreach ($this->getIconVariants() as $variantType => $directory) {
            $filename = $variantType === 'regular' ? "{$iconData['name']}.svg" : "{$iconData['name']}-{$variantType}.svg";
            $sourcePath = storage_path('app/temp_icons/'.self::ICON_SET_NAME."/temp/{$directory}/{$filename}");

            if (file_exists($sourcePath)) {
                $this->processIconVariant($icon, $filename, $sourcePath, $variantType);
            }
        }

        if (empty($icon->embedding) || $icon->wasChanged('keywords')) {
            GenerateIconEmbedding::dispatch($icon);
        }
    }

    private function processIconVariant(Icon $icon, string $filename, string $sourcePath, string $variantType): void
    {
        $destinationPath = 'icons/'.self::ICON_SET_NAME."/{$variantType}/{$filename}";

        $svgContent = file_get_contents($sourcePath);
        $processedSvg = $this->processSvg($svgContent);

        $variant = $icon->variants()->firstOrCreate(
            ['type' => $variantType],
            ['file_path' => "storage/{$destinationPath}"]
        );

        if (! $this->iconNeedsProcessing($variant, $processedSvg, $destinationPath)) {
            return;
        }

        Storage::put("public/{$destinationPath}", $processedSvg);

        $variant->update(['file_path' => "storage/{$destinationPath}"]);
    }

    private function processSvg(string $svgContent): string
    {
        return preg_replace('/fill="[^"]+"/', 'fill="currentColor"', $svgContent);
    }

    private function iconNeedsProcessing($variant, $processedSvg, $destinationPath): bool
    {
        if (! $variant->file_path || ! Storage::exists($variant->file_path)) {
            return true;
        }

        $sourceHash = md5($processedSvg);
        $destinationHash = md5(Storage::get($destinationPath));

        return $sourceHash !== $destinationHash;
    }

    public function cleanup(): void
    {
        Storage::deleteDirectory('temp_icons/'.self::ICON_SET_NAME);
    }

    // ... (keep other private methods like processIconVariant, processSvg, etc.)

    private function convertTypescriptToJson($typescriptContent): array
    {
        // Extract the icons array
        preg_match('/export const icons = \(<const>\[(.*?)\]\) satisfies readonly IconEntry\[\];/s', $typescriptContent, $matches);

        if (empty($matches[1])) {
            throw new \Exception('Icons not found in the file');
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

        // Filter out tags starting or ending with asterisks
        foreach ($transformedIcons as &$icon) {
            $icon['tags'] = array_values(array_filter($icon['tags'], function ($tag) {
                return ! str_starts_with($tag, '*') && ! str_ends_with($tag, '*');
            }));
        }

        return $transformedIcons;
    }
}
