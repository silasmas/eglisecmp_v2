<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Models\YoutubeSyncRun;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Exécute une synchronisation YouTube et persiste le résultat dans youtube_sync_runs.
 */
final class YoutubeSyncOrchestrator
{
    public function __construct(
        private readonly YoutubeChannelSyncService $syncService,
    ) {}

    /**
     * Lance une synchro et journalise le run (nouveau ou existant en file).
     *
     * @param  int|null  $existingRunId  ID d’un run « queued » créé avant lancement async.
     * @param  bool  $dryRun  Simulation sans écriture.
     * @param  bool  $full  Passe complète (ignore l’incrémental).
     * @param  string  $source  Origine : scheduler, filament, posts_page, queue, command.
     * @param  int|null  $userId  Utilisateur admin déclencheur.
     */
    public function run(
        ?int $existingRunId,
        bool $dryRun,
        bool $full,
        string $source,
        ?int $userId = null,
    ): YoutubeSyncRun {
        $run = $this->resolveRun($existingRunId, $dryRun, $full, $source, $userId);
        $run->markRunning();

        try {
            $result = $this->syncService->sync($dryRun, $full);

            if (! $result['ok']) {
                $run->markFailed($result['message'], $result);
                Log::warning('[youtube-sync] Échec run #'.$run->id.' : '.$result['message']);

                return $run->fresh() ?? $run;
            }

            $run->markSuccess($result);
            Log::info('[youtube-sync] Succès run #'.$run->id.' : '.$result['message']);

            return $run->fresh() ?? $run;
        } catch (Throwable $throwable) {
            $run->markFailed($throwable->getMessage());
            Log::error('[youtube-sync] Exception run #'.$run->id.' : '.$throwable->getMessage(), [
                'exception' => $throwable,
            ]);

            return $run->fresh() ?? $run;
        }
    }

    /**
     * Crée ou charge le run à mettre à jour.
     */
    private function resolveRun(
        ?int $existingRunId,
        bool $dryRun,
        bool $full,
        string $source,
        ?int $userId,
    ): YoutubeSyncRun {
        if ($existingRunId !== null) {
            $run = YoutubeSyncRun::query()->find($existingRunId);
            if ($run !== null) {
                return $run;
            }
        }

        return YoutubeSyncRun::query()->create([
            'status' => YoutubeSyncRun::STATUS_QUEUED,
            'source' => $source,
            'triggered_by_user_id' => $userId,
            'is_dry_run' => $dryRun,
            'is_full_sync' => $full,
        ]);
    }
}
