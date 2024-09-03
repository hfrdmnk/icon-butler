<?php

namespace App\Console\Commands;

use App\Jobs\GenerateIconEmbedding;
use App\Models\Icon;
use App\Models\Variant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchAndProcessIcons extends Command
{
    protected $signature = 'icons:fetch-and-process {--all : Process all icons}';

    protected $description = 'Fetch and process icons from Phosphor Icons repository';

    private $iconCounter = 0;

    public function handle()
    {
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
            $this->error('Failed to fetch icons from the repository.');
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
}
