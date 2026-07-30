<x-filament-panels::page>
  @php
    $templates = is_array($templates ?? null) ? $templates : [];
  @endphp

  <div class="space-y-6">
    <x-filament::section>
      <x-slot name="heading">Comment ça marche</x-slot>
      <ol class="list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>Téléchargez le <strong>modèle Excel</strong> (boutons en haut) pour départements, cellules ou extensions.</li>
        <li>Ouvrez le fichier <code>.xlsx</code> dans Excel / LibreOffice et complétez <strong>une ligne par enregistrement</strong>.</li>
        <li>Conservez la ligne d’en-têtes (première ligne).</li>
        <li>Cliquez sur <strong>Importer Excel</strong> pour synchroniser (création ou mise à jour).</li>
      </ol>
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">Colonnes des modèles</x-slot>
      <ul class="space-y-3">
        @foreach ($templates as $key => $meta)
          <li class="rounded-lg bg-gray-50 px-3 py-3 text-sm dark:bg-gray-800">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
              <span class="font-semibold text-gray-950 dark:text-white">{{ $meta['label'] }}</span>
              <x-filament::button
                wire:click="downloadTemplate('{{ $key }}')"
                color="gray"
                size="xs"
                icon="heroicon-o-arrow-down-tray"
              >
                Télécharger .xlsx
              </x-filament::button>
            </div>
            <code class="text-xs text-gray-600 dark:text-gray-300">{{ $meta['columns'] }}</code>
          </li>
        @endforeach
      </ul>
    </x-filament::section>

    @if ($lastMessage !== '')
      <x-filament::section>
        <x-slot name="heading">Dernier résultat</x-slot>
        <pre class="max-h-60 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $lastMessage }}</pre>
      </x-filament::section>
    @endif
  </div>
</x-filament-panels::page>
