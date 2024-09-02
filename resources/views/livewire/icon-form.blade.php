<?php

use Livewire\Volt\Component;

new class extends Component {
    public $copy;

    public function save()
    {
        $this->validate([
            'copy' => 'required',
        ]);

        // Save the data
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
</div>
