<x-filament-panels::page>
  @php
    $lastRun = is_array($status['last_run'] ?? null) ? $status['last_run'] : null;
    $lastRunAt = is_string($lastRun['ran_at'] ?? null) ? $lastRun['ran_at'] : null;
    $isHealthy = ($status['is_healthy'] ?? false) === true;
    $queueConnection = (string) ($status['queue_connection'] ?? 'sync');
    $httpUrl = $status['http_url'] ?? null;
    $tasks = is_array($status['tasks'] ?? null) ? $status['tasks'] : [];
  @endphp

  <div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-3">
      <x-filament::section>
        <x-slot name="heading">État</x-slot>
        <div class="flex items-center gap-3">
          <span @class([
            'inline-flex h-3 w-3 rounded-full',
            'bg-success-500' => $isHealthy,
            'bg-danger-500' => ! $isHealthy,
          ])></span>
          <span class="text-sm font-semibold text-gray-950 dark:text-white">
            {{ $isHealthy ? 'Opérationnel' : 'Attention requise' }}
          </span>
        </div>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          @if ($lastRunAt)
            Dernière exécution : {{ \Illuminate\Support\Carbon::parse($lastRunAt)->locale('fr')->diffForHumans() }}
            ({{ $lastRun['source'] ?? '—' }})
          @else
            Aucune exécution enregistrée pour le moment.
          @endif
        </p>
      </x-filament::section>

      <x-filament::section>
        <x-slot name="heading">File d’attente</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          <strong class="text-gray-950 dark:text-white">{{ strtoupper($queueConnection) }}</strong>
        </p>
        @if ($queueConnection === 'file')
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Jobs en attente : <strong>{{ (int) ($status['pending_queue_jobs'] ?? 0) }}</strong>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Avec <code>file</code>, lancez aussi <code>php artisan queue:work</code> si vous utilisez des jobs en file.
            Les tâches planifiées (YouTube sync auto, live, alertes) passent par <code>schedule:run</code>, pas par la file.
          </p>
        @elseif ($queueConnection === 'sync')
          <p class="mt-2 text-xs text-gray-500">
            Les jobs s’exécutent immédiatement dans la requête HTTP (adapté au dev local).
          </p>
        @endif
      </x-filament::section>

      <x-filament::section>
        <x-slot name="heading">Cron HTTP</x-slot>
        <div class="flex items-center justify-between gap-4">
          <span class="text-sm text-gray-600 dark:text-gray-400">Appel URL automatique</span>
          <x-filament::button
            wire:click="toggleHttpCron"
            :color="$httpCronEnabled ? 'success' : 'gray'"
            size="sm"
          >
            {{ $httpCronEnabled ? 'Activé' : 'Désactivé' }}
          </x-filament::button>
        </div>
        @if ($httpUrl)
          <p class="mt-3 break-all text-xs text-gray-500">
            URL (toutes les minutes) :<br />
            <code>{{ $httpUrl }}</code>
          </p>
        @else
          <p class="mt-3 text-xs text-warning-600">
            Définissez <code>DEPLOY_TOKEN</code> dans le .env pour générer l’URL.
          </p>
        @endif
      </x-filament::section>
    </div>

    <x-filament::section>
      <x-slot name="heading">Tâches planifiées</x-slot>
      <x-slot name="description">
        Équivalent de <code>php artisan schedule:run</code> (à lancer chaque minute via cron système ou l’URL ci-dessus).
      </x-slot>

      <div class="divide-y divide-gray-200 dark:divide-white/10">
        @foreach ($tasks as $task)
          @php
            $command = is_string($task['command'] ?? null) ? $task['command'] : '';
            $label = is_string($task['label'] ?? null) ? $task['label'] : $command;
            $frequency = is_string($task['frequency'] ?? null) ? $task['frequency'] : '';
          @endphp
          <div class="flex flex-wrap items-center justify-between gap-3 py-4">
            <div>
              <p class="font-semibold text-gray-950 dark:text-white">{{ $label }}</p>
              <p class="text-sm text-gray-500"><code>{{ $command }}</code> · {{ $frequency }}</p>
            </div>
            <x-filament::button
              wire:click="testCommand('{{ $command }}')"
              size="sm"
              color="gray"
              outlined
            >
              Tester
            </x-filament::button>
          </div>
        @endforeach
      </div>
    </x-filament::section>

    @if ($lastRun)
      <x-filament::section>
        <x-slot name="heading">Dernière sortie</x-slot>
        <pre class="max-h-64 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $lastRun['output'] ?? ($lastRun['error'] ?? '—') }}</pre>
      </x-filament::section>
    @endif

    <x-filament::section>
      <x-slot name="heading">Configuration production</x-slot>
      <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
        <p><strong>Option A — Cron système (recommandé)</strong></p>
        <pre class="rounded-lg bg-gray-950 p-3 text-xs text-gray-100">* * * * * cd /chemin/vers/eglisecmp_v2 && php artisan schedule:run >> /dev/null 2>&1</pre>
        <p><strong>Option B — Sans accès cron</strong></p>
        <ol>
          <li>Activez le bouton « Activé » ci-dessus.</li>
          <li>Sur <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> (gratuit), créez une tâche <em>every minute</em> qui appelle l’URL affichée.</li>
          <li>Cliquez « Exécuter le scheduler » pour vérifier immédiatement.</li>
        </ol>
        <p><strong>QUEUE_CONNECTION=file</strong> : les 3 tâches planifiées ci-dessus <em>ne nécessitent pas</em> <code>queue:work</code>. Seuls les jobs manuels (ex. bouton « Synchroniser YouTube ») peuvent en avoir besoin.</p>
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>
