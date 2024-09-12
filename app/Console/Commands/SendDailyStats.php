<?php

namespace App\Console\Commands;

use App\Models\IconSet;
use App\Models\Suggestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\SlackAlerts\Facades\SlackAlert;

class SendDailyStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'info:send-daily-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily statistics on icon suggestions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yesterday = now()->subDay();

        // Count total suggestions in the last 24 hours
        $totalSuggestions = Suggestion::where('created_at', '>=', $yesterday)
            ->count();

        $iconSets = Suggestion::where('created_at', '>=', $yesterday)
            ->get()
            ->groupBy('icon_set_id')
            ->map(function ($suggestions) {
                return [
                    'display_name' => IconSet::find($suggestions->first()->icon_set_id)->display_name,
                    'suggestions_count' => $suggestions->count(),
                ];
            })
            ->sortByDesc('suggestions_count');

        $iconSetsText = $iconSets->map(function ($iconSet) {
            return "• {$iconSet['display_name']}: {$iconSet['suggestions_count']} suggestions";
        })->join("\n");

        // Log::info($iconSetsText);

        SlackAlert::blocks(
            [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*Icon Butler Daily Stats*',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Total suggestions in the last 24 hours:* {$totalSuggestions}",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Most used icon sets:*\n{$iconSetsText}",
                    ],
                ],
                // [
                //     'type' => 'section',
                //     'text' => [
                //         'type' => 'mrkdwn',
                //         'text' => "*Most suggested icons:*\n{$topIconsText}",
                //     ],
                // ],
            ]
        );

        $this->info('Daily stats sent to Slack!');
    }
}
