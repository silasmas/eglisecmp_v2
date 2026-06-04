<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Mémorise l’état de la dernière synchro YouTube (cache) pour reprendre de façon incrémentale.
 */
final class YoutubeSyncState
{
    private const CACHE_KEY = 'youtube.channel.sync.state';

    /**
     * @return array{completed_at: string|null, playlists: array<string, array{itemCount: int}>}
     */
    public static function load(): array
    {
        $raw = Cache::get(self::CACHE_KEY);

        if (! is_array($raw)) {
            return [
                'completed_at' => null,
                'playlists' => [],
            ];
        }

        return [
            'completed_at' => is_string($raw['completed_at'] ?? null) ? $raw['completed_at'] : null,
            'playlists' => is_array($raw['playlists'] ?? null) ? $raw['playlists'] : [],
        ];
    }

    /**
     * @param  array<string, array{itemCount: int}>  $playlistCounts  Clé = ID playlist YouTube.
     */
    public static function save(array $playlistCounts): void
    {
        Cache::forever(self::CACHE_KEY, [
            'completed_at' => now()->toIso8601String(),
            'playlists' => $playlistCounts,
        ]);
    }

    /**
     * Date de la dernière synchro réussie ou null.
     */
    public static function lastCompletedAt(): ?Carbon
    {
        $state = self::load();
        $at = $state['completed_at'];

        return is_string($at) && $at !== '' ? Carbon::parse($at) : null;
    }

    /**
     * Compteur vidéos mémorisé pour une playlist.
     */
    public static function previousPlaylistItemCount(string $playlistId): ?int
    {
        $state = self::load();
        $row = $state['playlists'][$playlistId] ?? null;

        if (! is_array($row)) {
            return null;
        }

        return isset($row['itemCount']) ? (int) $row['itemCount'] : null;
    }
}
