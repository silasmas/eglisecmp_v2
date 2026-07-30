<x-filament-panels::page>
  @php
    $pendingCount = (int) ($status['pending_count'] ?? 0);
    $ranCount = (int) ($status['ran_count'] ?? 0);
    $filesCount = (int) ($status['migration_files_count'] ?? 0);
    $pending = is_array($status['pending'] ?? null) ? $status['pending'] : [];
    $lastRun = is_array($status['last_run'] ?? null) ? $status['last_run'] : null;
    $displayOutput = $lastOutput !== ''
      ? $lastOutput
      : (is_string($lastRun['output'] ?? null) ? $lastRun['output'] : '');
  @endphp

  <div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-3">
      <x-filament::section>
        <x-slot name="heading">État</x-slot>
        <div class="flex items-center gap-3">
          <span @class([
            'inline-flex h-3 w-3 rounded-full',
            'bg-success-500' => $pendingCount === 0,
            'bg-warning-500' => $pendingCount > 0,
          ])></span>
          <span class="text-sm font-semibold text-gray-950 dark:text-white">
            {{ $pendingCount === 0 ? 'Base à jour' : $pendingCount.' migration(s) en attente' }}
          </span>
        </div>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Migrations déjà appliquées : <strong>{{ $ranCount }}</strong>
          · Fichiers présents : <strong>{{ $filesCount }}</strong>
        </p>
      </x-filament::section>

      <x-filament::section>
        <x-slot name="heading">Dernière sync</x-slot>
        @if (is_array($lastRun) && filled($lastRun['ran_at'] ?? null))
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ \Illuminate\Support\Carbon::parse($lastRun['ran_at'])->locale('fr')->diffForHumans() }}
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Commande : <code>{{ $lastRun['command'] ?? '—' }}</code>
            · Source : {{ $lastRun['source'] ?? '—' }}
            · {{ ($lastRun['success'] ?? false) ? 'OK' : 'Échec' }}
          </p>
        @else
          <p class="text-sm text-gray-600 dark:text-gray-400">Aucune synchronisation encore enregistrée.</p>
        @endif
      </x-filament::section>

      <x-filament::section>
        <x-slot name="heading">Actions rapides</x-slot>
        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
          Applique les migrations une par une. Toute erreur ponctuelle (déjà présent, legacy, etc.) est ignorée et la sync continue.
        </p>
        <x-filament::button
          wire:click="runMigrations"
          wire:confirm="Confirmer l’exécution des migrations en attente ?"
          color="success"
          size="sm"
        >
          Synchroniser maintenant
        </x-filament::button>
      </x-filament::section>
    </div>

    <x-filament::section>
      <x-slot name="heading">Migrations en attente</x-slot>
      <x-slot name="description">
        Fichiers détectés comme non encore appliqués sur cette base.
      </x-slot>

      @if (count($pending) === 0)
        <p class="text-sm text-gray-600 dark:text-gray-400">Aucune migration en attente.</p>
      @else
        <ul class="space-y-2">
          @foreach ($pending as $migration)
            <li class="rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-900 dark:bg-warning-500/10 dark:text-warning-200">
              <code>{{ $migration }}</code>
            </li>
          @endforeach
        </ul>
      @endif
    </x-filament::section>

    @if ($displayOutput !== '')
      <x-filament::section>
        <x-slot name="heading">Sortie console</x-slot>
        <pre class="max-h-80 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $displayOutput }}</pre>
      </x-filament::section>
    @endif

    <x-filament::section>
      <x-slot name="heading">Lien HTTP (déploiement)</x-slot>
      <x-slot name="description">
        Même principe que Shield / storage-link : appel GET sécurisé par <code>DEPLOY_TOKEN</code>.
      </x-slot>
      @if (!empty($migrateHttpUrl))
        <p class="break-all rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">
          {{ $migrateHttpUrl }}
        </p>
        <p class="mt-2 text-xs text-gray-500">
          Ouvrez cette URL après un déploiement pour appliquer les migrations sans SSH.
        </p>
      @else
        <p class="text-sm text-warning-600 dark:text-warning-400">
          Définissez <code>DEPLOY_TOKEN</code> dans le <code>.env</code> pour activer le lien.
        </p>
      @endif
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">Bonnes pratiques</x-slot>
      <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
        <ol>
          <li>Déployez d’abord le code (nouveaux fichiers dans <code>database/migrations</code>).</li>
          <li>Cliquez sur <strong>Exécuter les migrations</strong> ou appelez le lien HTTP ci-dessus.</li>
          <li>Si de nouveaux modules Filament ont été ajoutés, lancez aussi <strong>Sync permissions Shield</strong>.</li>
          <li>Évitez <code>migrate:fresh</code> en production : cela effacerait les données.</li>
        </ol>
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>
