<?php

namespace App\Repositories;

use App\Contracts\IconRepositoryInterface;
use App\Jobs\GenerateIconEmbedding;
use App\Models\Icon;
use App\Models\IconSet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class LucideIconRepository implements IconRepositoryInterface
{
    private const ICON_SET_NAME = 'lucide';

    private const REPO_ZIP_URL = 'https://github.com/lucide-icons/lucide/archive/refs/heads/main.zip';

    public function downloadAndExtractRepo(): void
    {
        $zipPath = storage_path('app/temp_icons/'.self::ICON_SET_NAME.'/repo.zip');
        $zipDirectory = dirname($zipPath);
        $extractPath = storage_path('app/temp_icons/'.self::ICON_SET_NAME.'/temp');

        if (! is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

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
            'regular' => 'lucide-main/icons',
        ];
    }

    public function processIcon(array $iconData): void
    {
        $iconSet = IconSet::firstOrCreate(['name' => self::ICON_SET_NAME]);
        $isLogo = in_array('logo', $iconData['tags']);
        $companyName = $isLogo ? str_replace('-', ' ', $iconData['name']) : null;

        $icon = $iconSet->icons()->firstOrCreate([
            'name' => $iconData['name'],
            'is_logo' => $isLogo,
            'company_name' => $companyName,
            'keywords' => json_encode($iconData['tags']),
        ]);

        foreach ($this->getIconVariants() as $variantType => $directory) {
            $filename = "{$iconData['name']}.svg";
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
        $processedSvg = preg_replace('/\s(width|height)="\d+"/', '', $svgContent);

        return $processedSvg;
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

    public function storeMetadata(): void
    {
        $metadata = $this->prepareMetadata();
        Storage::put('temp_icons/'.self::ICON_SET_NAME.'/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
    }

    private function prepareMetadata(): array
    {
        $metadataPath = 'temp_icons/'.self::ICON_SET_NAME.'/metadata.json';

        if (! Storage::exists($metadataPath)) {
            $iconDirectory = storage_path('app/temp_icons/'.self::ICON_SET_NAME.'/temp/lucide-main/icons');
            $metadata = [];

            $jsonFiles = glob($iconDirectory.'/*.json');

            foreach ($jsonFiles as $jsonFile) {
                try {
                    $iconName = pathinfo($jsonFile, PATHINFO_FILENAME);
                    $jsonContent = json_decode(file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);

                    $metadata[] = [
                        'name' => $iconName,
                        'tags' => array_merge(
                            $jsonContent['tags'] ?? [],
                            // $jsonContent['categories'] ?? []
                        ),
                    ];
                } catch (\Exception $e) {
                    throw new \Exception('Failed to prepare metadata. '.$e->getMessage());
                }
            }

            return $metadata;
        }

        // If metadata file exists, you might want to log this information
        Log::info("Metadata file already exists: {$metadataPath}");

        return json_decode(Storage::get($metadataPath), true);
    }

    public function getTotalIconCount(): int
    {
        $metadata = $this->getMetadata();

        return count($metadata);
    }

    public function processIcons(bool $processAll, callable $progressCallback): void
    {
        $metadata = $this->getMetadata();

        foreach ($metadata as $index => $iconData) {
            if ($processAll || $index % 10 === 0) {
                $this->processIcon($iconData);
            }
            $progressCallback();
        }
    }

    private function getMetadata(): array
    {
        $metadataPath = 'temp_icons/'.self::ICON_SET_NAME.'/metadata.json';

        if (! Storage::exists($metadataPath)) {
            throw new \Exception('Metadata file not found.');
        }

        return json_decode(Storage::get($metadataPath), true);
    }
}
