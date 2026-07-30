<x-filament-panels::page>
  @php
    $pendingCount = (int) ($status['pending_count'] ?? 0);
    $ranCount = (int) ($status['ran_count'] ?? 0);
    $filesCount = (int) ($status['migration_files_count'] ?? 0);
    $pending = is_array($status['pending'] ?? null) ? $status['pending'] : [];
    $lastRun = is_array($status['last_run'] ?? null) ? $status['last_run'] : null;
    $safeSeeders = is_array($safeSeeders ?? null) ? $safeSeeders : [];
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
          Migrations une par une, puis seeders de référence (départements, etc.).
        </p>
        <div class="flex flex-wrap gap-2">
          <x-filament::button
            wire:click="runMigrations"
            wire:confirm="Confirmer l’exécution des migrations en attente ?"
            color="success"
            size="sm"
          >
            Synchroniser migrations
          </x-filament::button>
          <x-filament::button
            wire:click="runSeeders"
            wire:confirm="Confirmer l’exécution des seeders (départements, extensions, stats) ?"
            color="info"
            size="sm"
          >
            Lancer les seeders
          </x-filament::button>
        </div>
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

    <x-filament::section>
      <x-slot name="heading">Seeders de référence</x-slot>
      <x-slot name="description">
        Classes idempotentes exécutées par « Lancer les seeders » (pas de factories / import SQL).
      </x-slot>
      @if (count($safeSeeders) === 0)
        <p class="text-sm text-gray-600 dark:text-gray-400">Aucun seeder configuré.</p>
      @else
        <ul class="space-y-2">
          @foreach ($safeSeeders as $seeder)
            <li class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">
              <code>{{ $seeder }}</code>
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
      <x-slot name="heading">Liens HTTP (déploiement)</x-slot>
      <x-slot name="description">
        Appels GET sécurisés par <code>DEPLOY_TOKEN</code>.
      </x-slot>
      @if (!empty($migrateHttpUrl))
        <p class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Migrations</p>
        <p class="mb-3 break-all rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">
          {{ $migrateHttpUrl }}
        </p>
      @endif
      @if (!empty($seedHttpUrl))
        <p class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">Seeders</p>
        <p class="mb-3 break-all rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">
          {{ $seedHttpUrl }}
        </p>
      @endif
      @if (empty($migrateHttpUrl) && empty($seedHttpUrl))
        <p class="text-sm text-warning-600 dark:text-warning-400">
          Définissez <code>DEPLOY_TOKEN</code> dans le <code>.env</code> pour activer les liens.
        </p>
      @else
        <p class="text-xs text-gray-500">
          Après un déploiement : migrations d’abord, puis seeders si besoin (départements, etc.).
        </p>
      @endif
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">Bonnes pratiques</x-slot>
      <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
        <ol>
          <li>Déployez d’abord le code (nouveaux fichiers dans <code>database/migrations</code> et <code>database/seeders</code>).</li>
          <li>Exécutez les <strong>migrations</strong>, puis les <strong>seeders</strong> (départements ouvriers, etc.).</li>
          <li>Si de nouveaux modules Filament ont été ajoutés, lancez aussi <strong>Sync permissions Shield</strong>.</li>
          <li>Évitez <code>migrate:fresh</code> en production : cela effacerait les données.</li>
        </ol>
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>
