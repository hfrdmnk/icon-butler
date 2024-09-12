<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class FetchAndProcessIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icons:fetch-and-process {iconSet=allIconSets} {--all : Process all icons}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and process icons from the configured icon repository';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $iconSet = $this->argument('iconSet');

        if ($iconSet === 'allIconSets') {
            $this->processAllIconSets();
        } else {
            $this->processSingleIconSet($iconSet);
        }
    }

    /**
     * Process all icon sets.
     */
    private function processAllIconSets()
    {
        $iconSets = ['phosphor', 'lucide']; // Add all your icon sets here

        foreach ($iconSets as $iconSet) {
            $this->info("Processing $iconSet icons...");
            $this->processSingleIconSet($iconSet);
            $this->info("$iconSet icons processed successfully.");
            $this->newLine();
        }
    }

    /**
     * Process a single icon set.
     */
    private function processSingleIconSet($iconSet)
    {
        $repository = app("icon.repository.$iconSet");

        $this->info("Fetching and processing $iconSet icons...");

        $repository->downloadAndExtractRepo();
        $repository->storeMetadata();

        $processAll = $this->option('all') || App::environment('production');
        $this->processIcons($processAll, $repository);

        $repository->cleanup();

        $this->info("$iconSet icons fetched and processed successfully.");
        $this->info('Temporary data has been cleaned up.');
    }

    /**
     * Process icons.
     */
    private function processIcons(bool $processAll, $repository)
    {
        $totalIcons = $repository->getTotalIconCount();
        $bar = $this->output->createProgressBar($totalIcons);
        $bar->start();

        $repository->processIcons($processAll, function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        if (! $processAll) {
            $this->info('Processed every 10th icon. Use --all flag to process all icons.');
        }
    }
}
