<?php

use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Icon;

new class extends Component {
    public $copy;
    public $suggestedIcons = [];
    public $explanations = [];
    public $selectedVariant = [];
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
        $this->suggestedIcons = $iconSimilarities->take(3)->map(function ($item) {
            return $item['icon'];
        });

        // Generate explanations
        foreach ($this->suggestedIcons as $icon) {
            $this->explanations[$icon->id] = $this->generateExplanation($icon->name, $this->copy);
            $this->selectedVariant[$icon->id] = $icon->variants->first()->type;
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

    public function copyToClipboard($iconId, $variantType)
    {
        $icon = $this->suggestedIcons->firstWhere('id', $iconId);
        $variant = $icon->variants->firstWhere('type', $variantType);
        $this->dispatch('alert', message: 'Icon path: ' . $variant->file_path);
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
}; ?>

<div>
    <x-mary-form wire:submit="save">
        <div class="flex flex-col gap-4">
            <x-mary-textarea
                label="Your Copy"
                wire:model="copy"
                hint="Paste your copy in here"
                placeholder="Lorem Ipsum…"
                rows="8"
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
            @foreach ($suggestedIcons as $icon)
                <div class="mb-6 rounded-lg border p-4">
                    <h4 class="mb-2 text-lg font-semibold">{{ $icon->name }}</h4>
                    <div class="mb-4 flex items-center">
                        <div class="mr-4 h-16 w-16">
                            @php
                                $variant = $icon->variants->firstWhere('type', $selectedVariant[$icon->id]);
                                $svgContent = file_get_contents(storage_path('app/' . $variant->file_path));
                            @endphp

                            {!! $svgContent !!}
                        </div>
                        <div>
                            <select wire:model="selectedVariant.{{ $icon->id }}" class="mb-2">
                                @foreach ($icon->variants as $variant)
                                    <option value="{{ $variant->type }}">{{ ucfirst($variant->type) }}</option>
                                @endforeach
                            </select>
                            <x-mary-button
                                label="Copy SVG"
                                size="sm"
                                wire:click="copyToClipboard({{ $icon->id }}, '{{ $selectedVariant[$icon->id] }}')"
                            />
                        </div>
                    </div>
                    <p class="text-sm">{{ $explanations[$icon->id] }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>

@script
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('alert', ({ message }) => {
                alert(message);
            });
        });
    </script>
@endscript
