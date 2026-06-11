<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Youtube\YoutubeSyncOrchestrator;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Console\Command;

/**
 * Synchronise la chaîne YouTube vers les tables posts et events.
 */
class SyncYoutubeChannelCommand extends Command
{
    protected $signature = 'youtube:sync
        {--dry-run : Simule sans écrire en base}
        {--full : Ignore l’état incrémental et rescanne tout}
        {--run-id= : ID d’un run déjà créé (lancement async)}
        {--source=command : Origine du déclenchement (scheduler, filament…)}';

    protected $description = 'Importe vidéos, shorts et playlists YouTube vers les publications (enseignements)';

    public function handle(YoutubeSyncOrchestrator $orchestrator, YoutubeLiveStatusService $live): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $full = (bool) $this->option('full');
        $runId = $this->option('run-id');
        $source = (string) $this->option('source');

        $parsedRunId = is_numeric($runId) ? (int) $runId : null;

        $this->info($dryRun ? 'Simulation de synchronisation YouTube…' : 'Synchronisation YouTube en cours…');

        $liveNow = $live->current();
        if ($liveNow !== null) {
            $this->line('<fg=red>● Live en cours sur YouTube :</> '.($liveNow['title'] ?? ''));
        } else {
            $this->line('○ Aucun live YouTube détecté pour le moment.');
        }

        $run = $orchestrator->run($parsedRunId, $dryRun, $full, $source);

        if ($run->status === \App\Models\YoutubeSyncRun::STATUS_FAILED) {
            $this->error($run->error_message ?? 'Échec de la synchronisation.');

            return self::FAILURE;
        }

        $this->info($run->message ?? 'Synchronisation terminée.');
        $this->table(
            ['Playlists', 'Vidéos lues', 'Créées', 'Mises à jour', 'Déjà à jour', 'Ignorées'],
            [[
                $run->playlists,
                $run->videos,
                $run->created_count,
                $run->updated_count,
                $run->unchanged_count,
                $run->skipped_count,
            ]],
        );

        return self::SUCCESS;
    }
}
