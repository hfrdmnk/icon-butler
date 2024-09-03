<?php

use Livewire\Volt\Component;
use OpenAI\Laravel\Facades\OpenAI;

new class extends Component {
    public $copy;
    public $response;

    public function save()
    {
        $promptStart =
            "Suggest me three icon names from the Phosphor icon set that could suit this copy. Make sure they exist in the Posphor Icon Library. Give me their official names. Below is the copy from the user.\n---\n";

        $this->validate([
            'copy' => 'required',
        ]);

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $promptStart . ' ' . $this->copy]],
        ]);

        $this->response = $result->choices[0]->message->content;
    }
}; ?>

<div>
    <x-mary-form wire:submit="save">
        <x-mary-textarea
            label="Your Copy"
            wire:model="copy"
            hint="Paste your copy in here"
            placeholder="Lorem Ipsum…"
        />

        <x-slot:actions>
            <x-mary-button label="Give me some icons!" class="btn-primary" type="submit" spinner="save" />
        </x-slot>
    </x-mary-form>
    @if ($response)
        <div class="mt-4">{{ $response }}</div>
    @endif
</div>
