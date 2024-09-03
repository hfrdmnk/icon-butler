<?php

use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Icon;

new class extends Component {
    public $copy;
    public $suggestedIcons = [];
    public $explanations = [];
    public $selectedVariant = [];

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

        // Calculate cosine similarity and sort
        $iconSimilarities = $icons
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
        $svgContent = file_get_contents(storage_path('app/' . $variant->file_path));
        $this->dispatch('copyToClipboard', svg: $svgContent);
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
        <x-mary-textarea
            label="Your Copy"
            wire:model="copy"
            hint="Paste your copy in here"
            placeholder="Lorem Ipsum…"
            rows="10"
        />

        <x-slot:actions>
            <x-mary-button label="Suggest Icons" class="btn-primary" type="submit" spinner="save" />
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

<script>
    document.addEventListener('livewire:initialized', () => {
        @this.on('copyToClipboard', (event) => {
            navigator.clipboard.writeText(event.svg).then(() => {
                alert('SVG copied to clipboard!');
            });
        });
    });
</script>
