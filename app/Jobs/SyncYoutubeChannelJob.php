<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Youtube\YoutubeChannelSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Synchronisation YouTube en arrière-plan (évite le timeout HTTP 504 sur l’admin).
 */
class SyncYoutubeChannelJob implements ShouldQueue
{
    use Queueable;

    /** @var int Timeout en secondes (≈ 20 min pour 100+ playlists). */
    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * Lance l’import chaîne → posts / événements.
     */
    public function handle(YoutubeChannelSyncService $sync): void
    {
        $result = $sync->sync(false);

        Log::info('[youtube-sync-job] '.$result['message'], [
            'playlists' => $result['playlists'],
            'videos' => $result['videos'],
            'created' => $result['created'],
            'updated' => $result['updated'],
            'unchanged' => $result['unchanged'] ?? 0,
            'skipped' => $result['skipped'],
        ]);

        if (! $result['ok']) {
            Log::error('[youtube-sync-job] Échec : '.$result['message']);
        }
    }
}
