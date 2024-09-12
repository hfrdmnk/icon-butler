<?php

namespace App\Console\Commands;

use App\Facades\IconRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class FetchAndProcessIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icons:fetch-and-process {--all : Process all icons}';

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
        $this->info('Fetching and processing icons...');

        IconRepository::downloadAndExtractRepo();
        IconRepository::generateMetadata();

        $processAll = $this->option('all') || App::environment('production');
        $this->processIcons($processAll);

        IconRepository::cleanup();

        $this->info('Icons fetched and processed successfully.');
        $this->info('Temporary data has been cleaned up.');
    }

    private function processIcons(bool $processAll)
    {
        $totalIcons = IconRepository::getTotalIconCount();
        $bar = $this->output->createProgressBar($totalIcons);
        $bar->start();

        IconRepository::processIcons($processAll, function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        if (! $processAll) {
            $this->info('Processed every 10th icon. Use --all flag to process all icons.');
        }
    }
}
