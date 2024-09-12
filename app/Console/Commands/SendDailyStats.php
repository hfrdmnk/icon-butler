<?php

namespace App\Console\Commands;

use App\Models\Icon;
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

        $mostSuggestedIcons =
            Icon::withCount(['suggestions' => function ($query) use ($yesterday) {
                $query->where('icon_suggestion.created_at', '>=', $yesterday);
            }])
            ->orderBy('suggestions_count', 'desc')
            ->take(5)
            ->get();

        $topIconsText = $mostSuggestedIcons->map(function ($icon) {
            return "• {$icon->name}: {$icon->suggestions_count} suggestions";
        })->join("\n");

        // Log::info($topIconsText);

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
                        'text' => "*Most suggested icons:*\n{$topIconsText}",
                    ],
                ],
            ]
        );

        $this->info('Daily stats sent to Slack!');
    }
}
