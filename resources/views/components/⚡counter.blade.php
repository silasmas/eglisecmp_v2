<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div class="mx-auto max-w-xl rounded-lg bg-white p-6 shadow">
    <h1 class="text-2xl font-semibold text-gray-800">Compteur Livewire</h1>
    <p class="mt-2 text-gray-600">Valeur actuelle: {{ $count }}</p>

    <button
        type="button"
        wire:click="increment"
        class="mt-4 rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
    >
        Incrémenter
    </button>
</div>