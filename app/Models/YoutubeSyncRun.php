<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Journal d’une exécution de synchronisation YouTube (manuelle, cron ou file).
 */
class YoutubeSyncRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'status',
        'source',
        'triggered_by_user_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'message',
        'error_message',
        'playlists',
        'videos',
        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',
        'is_dry_run',
        'is_full_sync',
        'output_log',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'is_dry_run' => 'boolean',
            'is_full_sync' => 'boolean',
        ];
    }

    /**
     * Utilisateur admin ayant déclenché la synchro (si applicable).
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * Marque la synchro comme démarrée.
     */
    public function markRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Enregistre un succès avec les compteurs de la passe.
     *
     * @param  array{message: string, playlists: int, videos: int, created: int, updated: int, unchanged?: int, skipped: int}  $result
     */
    public function markSuccess(array $result, ?string $outputLog = null): void
    {
        $finishedAt = now();
        $startedAt = $this->started_at instanceof Carbon ? $this->started_at : $finishedAt;

        $this->update([
            'status' => self::STATUS_SUCCESS,
            'finished_at' => $finishedAt,
            'duration_seconds' => max(0, $startedAt->diffInSeconds($finishedAt)),
            'message' => $result['message'],
            'error_message' => null,
            'playlists' => (int) $result['playlists'],
            'videos' => (int) $result['videos'],
            'created_count' => (int) $result['created'],
            'updated_count' => (int) $result['updated'],
            'unchanged_count' => (int) ($result['unchanged'] ?? 0),
            'skipped_count' => (int) $result['skipped'],
            'output_log' => $outputLog,
        ]);
    }

    /**
     * Enregistre un échec avec le message d’erreur.
     *
     * @param  array{playlists?: int, videos?: int, created?: int, updated?: int, unchanged?: int, skipped?: int}|null  $partial
     */
    public function markFailed(string $errorMessage, ?array $partial = null, ?string $outputLog = null): void
    {
        $finishedAt = now();
        $startedAt = $this->started_at instanceof Carbon ? $this->started_at : $finishedAt;

        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => $finishedAt,
            'duration_seconds' => max(0, $startedAt->diffInSeconds($finishedAt)),
            'error_message' => $errorMessage,
            'playlists' => (int) ($partial['playlists'] ?? $this->playlists),
            'videos' => (int) ($partial['videos'] ?? $this->videos),
            'created_count' => (int) ($partial['created'] ?? $this->created_count),
            'updated_count' => (int) ($partial['updated'] ?? $this->updated_count),
            'unchanged_count' => (int) ($partial['unchanged'] ?? $this->unchanged_count),
            'skipped_count' => (int) ($partial['skipped'] ?? $this->skipped_count),
            'output_log' => $outputLog,
        ]);
    }

    /**
     * Libellé français de la source de déclenchement.
     */
    public function sourceLabel(): string
    {
        return match ($this->source) {
            'scheduler' => 'Cron (planificateur)',
            'scheduler_manual' => 'Admin — test scheduler',
            'filament' => 'Admin — page synchro',
            'posts_page' => 'Admin — publications',
            'queue' => 'File d’attente',
            'command' => 'Ligne de commande',
            default => $this->source,
        };
    }

    /**
     * Libellé français du statut.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'En file',
            self::STATUS_RUNNING => 'En cours',
            self::STATUS_SUCCESS => 'Réussie',
            self::STATUS_FAILED => 'Échouée',
            default => $this->status,
        };
    }
}
