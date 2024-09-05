<?php

namespace App\Console\Commands;

use App\Facades\IconRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class FetchAndProcessIcons extends Command
{
    protected $signature = 'icons:fetch-and-process {--all : Process all icons} {--json-only : Only fetch and save JSON without processing icons}';

    protected $description = 'Fetch and process icons from the configured icon repository';

    public function handle()
    {
        $this->createJsonFileFromMetadata();

        if ($this->option('json-only')) {
            return;
        }

        IconRepository::downloadAndExtractRepo();

        $this->processIcons();

        IconRepository::cleanup();

        $this->info('Icons fetched and processed successfully.');
        $this->info('Temporary data has been cleaned up.');
    }

    private function createJsonFileFromMetadata()
    {
        $metadata = IconRepository::fetchMetadata();
        Storage::put('temp_icons/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
        $this->info('Successfully created metadata.json file.');
    }

    private function processIcons()
    {
        $metadata = json_decode(Storage::get('temp_icons/metadata.json'), true);

        $this->info('Processing icons...');
        $bar = $this->output->createProgressBar(count($metadata));
        $bar->start();

        $processAll = $this->option('all') || App::environment('production');

        foreach ($metadata as $index => $iconData) {
            if ($processAll || $index % 10 === 0) {
                IconRepository::processIcon($iconData);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if (! $processAll) {
            $this->info('Processed every 10th icon. Use --all flag to process all icons.');
        }
    }
}
