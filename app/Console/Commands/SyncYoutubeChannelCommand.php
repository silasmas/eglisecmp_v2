<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Youtube\YoutubeChannelSyncService;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Console\Command;

/**
 * Synchronise la chaîne YouTube vers les tables posts et events.
 */
class SyncYoutubeChannelCommand extends Command
{
    protected $signature = 'youtube:sync {--dry-run : Simule sans écrire en base} {--full : Ignore l’état incrémental et rescanne tout}';

    protected $description = 'Importe vidéos, shorts et playlists YouTube vers les publications (enseignements)';

    public function handle(YoutubeChannelSyncService $sync, YoutubeLiveStatusService $live): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Simulation de synchronisation YouTube…' : 'Synchronisation YouTube en cours…');

        $liveNow = $live->current();
        if ($liveNow !== null) {
            $this->line('<fg=red>● Live en cours sur YouTube :</> '.($liveNow['title'] ?? ''));
        } else {
            $this->line('○ Aucun live YouTube détecté pour le moment.');
        }

        $full = (bool) $this->option('full');
        $result = $sync->sync($dryRun, $full);

        if (! $result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->table(
            ['Playlists', 'Vidéos lues', 'Créées', 'Mises à jour', 'Déjà à jour', 'Ignorées'],
            [[
                $result['playlists'],
                $result['videos'],
                $result['created'],
                $result['updated'],
                $result['unchanged'] ?? 0,
                $result['skipped'],
            ]],
        );

        return self::SUCCESS;
    }
}
