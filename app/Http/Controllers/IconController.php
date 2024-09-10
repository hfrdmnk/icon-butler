<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use App\Models\IconVariant;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OpenAI\Laravel\Facades\OpenAI;

class IconController extends Controller
{
    public function index()
    {
        return Inertia::render('Icons/Index');
    }

    public function suggestIcons(Request $request)
    {
        $request->validate([
            'copy' => 'required|min:3',
            'excludeUnmentionedLogos' => 'boolean',
        ]);

        $copy = $request->input('copy');
        $excludeUnmentionedLogos = $request->input('excludeUnmentionedLogos');

        // Generate embedding for user's copy
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $copy,
        ]);
        $copyEmbedding = $response->embeddings[0]->embedding;

        // Retrieve all icons with embeddings
        $icons = Icon::whereNotNull('embedding')
            ->with('variants')
            ->get();

        // Filter icons based on name and explicit mention in copy
        $filteredIcons = $icons->filter(function ($icon) use ($copy, $excludeUnmentionedLogos) {
            if (str_contains(strtolower($icon->name), 'logo') && $excludeUnmentionedLogos) {
                $companyName = str_replace('-logo', '', strtolower($icon->name));

                return str_contains(strtolower($copy), $companyName);
            }

            return true;
        });

        // Calculate cosine similarity and sort
        $iconSimilarities = $filteredIcons
            ->map(function ($icon) use ($copyEmbedding) {
                $iconEmbedding = json_decode($icon->embedding);
                $similarity = $this->cosineSimilarity($copyEmbedding, $iconEmbedding);

                return ['icon' => $icon, 'similarity' => $similarity];
            })
            ->sortByDesc('similarity');

        // Get top 4 matches
        $suggestedIcons = $iconSimilarities->take(4)->map(function ($item) use ($copy) {
            $icon = $item['icon'];
            $explanation = $this->generateExplanation($icon->name, $copy);
            $iconData = $icon->variants->mapWithKeys(function ($variant) {
                return [
                    $variant->type => [
                        'file_path' => $variant->file_path,
                        'svg_content' => file_get_contents(public_path($variant->file_path)),
                    ],
                ];
            })->toArray();

            return [
                'id' => $icon->id,
                'name' => $icon->name,
                'explanation' => $explanation,
                'iconData' => $iconData,
            ];
        })->values()->all();

        $suggestion = Suggestion::create(['copy' => $copy]);
        $suggestedIconIds = collect($suggestedIcons)->pluck('id')->all();
        $suggestion->icons()->attach($suggestedIconIds);

        $availableTypes =
            IconVariant::select('type')
                ->distinct()
                ->pluck('type')
                ->map(function ($type) {
                    return [
                        'id' => $type,
                        'name' => ucfirst($type),
                    ];
                });

        return Inertia::render('Icons/Index', [
            'copy' => $copy,
            'excludeUnmentionedLogos' => $excludeUnmentionedLogos,
            'suggestedIcons' => $suggestedIcons,
            'availableTypes' => $availableTypes,
        ]);
    }

    private function generateExplanation($iconName, $copy)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that explains why an icon fits a given copy.',
                ],
                [
                    'role' => 'user',
                    'content' => "Explain in one short sentence why the '{$iconName}' icon might fit this copy: '{$copy}'. Don't start with something along the lines of 'this icon fits this copy because'. Just say something like the 'xy' icon represents…",
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    private function cosineSimilarity($a, $b)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $magnitudeA += $value * $value;
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
