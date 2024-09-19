<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use App\Models\IconSet;
use App\Models\IconVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use OpenAI\Laravel\Facades\OpenAI;

class IconController extends Controller
{
    public function index()
    {
        return Inertia::render('Icons/Index', [
            'iconSets' => IconSet::all(),
        ]);
    }

    public function suggestIcons(Request $request)
    {
        $validated = $request->validate([
            'copy' => ['required', 'min:3'],
            'iconSet' => ['required', 'exists:icon_sets,name'],
            'excludeUnmentionedLogos' => ['required', 'boolean'],
            'alreadySuggestedIcons' => ['nullable', 'array'],
            'keywordsEmbedding' => ['nullable', 'array'],
            'suggestionId' => ['nullable', 'exists:suggestions,id'],
        ]);

        $copy = $validated['copy'];
        $iconSetName = $validated['iconSet'];
        $excludeUnmentionedLogos = $validated['excludeUnmentionedLogos'];
        $alreadySuggestedIcons = $validated['alreadySuggestedIcons'] ?? [];
        $keywordsEmbedding = $validated['keywordsEmbedding'] ?? null;
        $suggestionId = $validated['suggestionId'] ?? null;

        if (!$keywordsEmbedding) {
            // Based on the copy, generate keywords that can be embedded
            $keywordsResponse = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that can take a piece of ui copy and turn it into high-level keywords (think categories, abstract concepts, action verbs, adjectives, …). You always return between 3 and 10 keywords (depending on how much you think are needed to describe the copy as acurately as possible), the first one being the most important, the last one being the least important. If a brand name is mentioned, you must include it as a keyword. You only respond in English, no matter the input language.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Return a comma-separated list of keywords for the following copy: '{$copy}'",
                    ],
                ],
            ]);

            $generatedKeywordsToEmbed = strtolower($keywordsResponse->choices[0]->message->content);

            // Generate embedding for user's copy
            $embeddingResponse = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $generatedKeywordsToEmbed,
            ]);
            $keywordsEmbedding = $embeddingResponse->embeddings[0]->embedding;
        }

        // Retrieve icon set
        $iconSet = IconSet::where('name', $iconSetName)->first();

        // Retrieve all icons with embeddings
        $icons = $iconSet
            ->icons()
            ->whereNotNull('embedding')
            ->with('variants')
            ->get();

        // Filter icons based on name and explicit mention in copy
        $filteredIcons = $icons->filter(function ($icon) use ($copy, $excludeUnmentionedLogos) {
            if ($icon->is_logo && $excludeUnmentionedLogos) {
                $companyName = strtolower($icon->company_name);
                $copyLower = strtolower($copy);

                // Create variations of the company name
                $variations = [
                    $companyName,
                    str_replace(' ', '-', $companyName),
                    str_replace(' ', '.', $companyName),
                ];

                // Check if any variation is present in the copy
                return collect($variations)->contains(function ($variation) use ($copyLower) {
                    return str_contains($copyLower, $variation);
                });
            }

            return true;
        });

        // Remove already suggested icons
        $filteredIcons = $filteredIcons->reject(function ($icon) use ($alreadySuggestedIcons) {
            return in_array($icon->id, $alreadySuggestedIcons);
        });

        // Calculate cosine similarity and sort
        $iconSimilarities = $filteredIcons
            ->map(function ($icon) use ($keywordsEmbedding) {
                $iconEmbedding = json_decode($icon->embedding);
                $similarity = $this->cosineSimilarity($keywordsEmbedding, $iconEmbedding);

                return ['icon' => $icon, 'similarity' => $similarity];
            })
            ->sortByDesc('similarity');

        // Get top 4 matches
        $suggestedIcons = $iconSimilarities->take(4)->map(function ($item) use ($copy) {
            $icon = $item['icon'];
            // $explanation = $this->generateExplanation($icon->name, $copy);
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
                // 'explanation' => $explanation,
                'iconData' => $iconData,
            ];
        })->values()->all();

        if (!$suggestionId) {
            $suggestion = $iconSet->suggestions()->create(['copy' => $copy, 'exclude_unmentioned_logos' => $excludeUnmentionedLogos, 'generated_keywords' => $generatedKeywordsToEmbed]);
        } else {
            $suggestion = $iconSet->suggestions()->find($suggestionId);
        }

        $suggestedIconIds = collect($suggestedIcons)->pluck('id')->all();
        $suggestion->icons()->attach($suggestedIconIds);

        $availableTypes =
            IconVariant::select('type')
            ->whereIn('icon_id', $filteredIcons->pluck('id'))
            ->distinct()
            ->pluck('type')
            ->map(function ($type) {
                return [
                    'id' => $type,
                    'name' => ucfirst($type),
                ];
            });

        return Inertia::render('Icons/Index', [
            'iconSets' => IconSet::all(),
            'iconSet' => $iconSetName,
            'copy' => $copy,
            'excludeUnmentionedLogos' => $excludeUnmentionedLogos,
            'suggestedIcons' => $suggestedIcons,
            'keywordsEmbedding' => $keywordsEmbedding,
            'suggestionId' => $suggestion->id,
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
