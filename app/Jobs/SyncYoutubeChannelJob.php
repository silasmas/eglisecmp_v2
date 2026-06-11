<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Youtube\YoutubeSyncOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
     * @param  int|null  $syncRunId  Run journalisé créé avant dispatch.
     * @param  bool  $full  Passe complète.
     */
    public function __construct(
        public ?int $syncRunId = null,
        public bool $full = false,
    ) {}

    /**
     * Lance l’import chaîne → posts / événements.
     */
    public function handle(YoutubeSyncOrchestrator $orchestrator): void
    {
        $orchestrator->run($this->syncRunId, false, $this->full, 'queue');
    }
}
