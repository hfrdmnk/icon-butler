<?php

use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Icon;

new class extends Component {
    public $copy;
    public $suggestedIcons = [];
    public $iconPaths = [];
    public $explanations = [];
    public $selectedVariant = 'regular';
    public $excludeUnmentionedLogos = true;

    public function save()
    {
        $this->validate([
            'copy' => 'required|min:3',
        ]);

        // Generate embedding for user's copy
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $this->copy,
        ]);
        $copyEmbedding = $response->embeddings[0]->embedding;

        // Retrieve all icons with embeddings
        $icons = Icon::whereNotNull('embedding')
            ->with('variants')
            ->get();

        // Filter icons based on name and explicit mention in copy
        $filteredIcons = $icons->filter(function ($icon) {
            if (str_contains(strtolower($icon->name), 'logo') && $this->excludeUnmentionedLogos) {
                $companyName = str_replace('-logo', '', strtolower($icon->name));
                return str_contains(strtolower($this->copy), $companyName);
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

        // Get top 3 matches
        $this->suggestedIcons = $iconSimilarities->take(4)->map(function ($item) {
            return $item['icon'];
        });

        // Generate explanations
        foreach ($this->suggestedIcons as $icon) {
            $this->explanations[$icon->id] = $this->generateExplanation($icon->name, $this->copy);
            $this->iconPaths[$icon->id] = $this->suggestedIcons
                ->firstWhere('id', $icon->id)
                ->variants->firstWhere('type', $icon->selectedVariant ?? 'regular')->file_path;
        }
    }

    public function generateExplanation($iconName, $copy)
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
                    'content' => "Explain in one short scentence why the '{$iconName}' icon might fit this copy: '{$copy}'",
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

    public function changeVariant($variant, $iconId)
    {
        $icon = $this->suggestedIcons->firstWhere('id', $iconId);
        if ($icon) {
            $icon->selectedVariant = $variant;
        }
    }
}; ?>

<div>
    <x-mary-form wire:submit="save">
        <div class="flex flex-col gap-4">
            <x-mary-textarea
                label="Your Copy"
                wire:model="copy"
                hint="Paste your copy in here"
                placeholder="Lorem Ipsum…"
                x-data
                x-autosize
            />
            <x-mary-checkbox
                label="Exclude company logos not explicitly mentioned"
                wire:model="excludeUnmentionedLogos"
                hint="This usually results in a more accurate match."
            />
        </div>

        <x-slot:actions>
            <x-mary-button class="btn-primary" type="submit" spinner="save">
                <span wire:loading.remove>Suggest Icons</span>
                <span wire:loading>Looking for the perfect match</span>
            </x-mary-button>
        </x-slot>
    </x-mary-form>

    @if (count($suggestedIcons) > 0)
        <div class="mt-4">
            <h3 class="mb-4 text-xl font-semibold">Suggested Icons:</h3>

            <div class="grid grid-cols-2 gap-4">
                @foreach ($suggestedIcons as $icon)
                    <div class="flex flex-col gap-2 rounded border border-base-300 p-4">
                        <div class="flex items-center">
                            <div class="mr-2 h-8 w-8">
                                <img src="{{ asset($iconPaths[$icon->id]) }}" alt="{{ $icon->name }}" />
                            </div>
                            <h4 class="text-sm font-medium">{{ $icon->name }}</h4>
                        </div>
                        <div class="text-sm text-base-content-muted">
                            {{ $explanations[$icon->id] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
