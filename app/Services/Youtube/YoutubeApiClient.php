<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Support\YoutubeDurationParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP minimal pour l’API YouTube Data v3 (chaîne, playlists, vidéos).
 */
final class YoutubeApiClient
{
    /**
     * @return string|null ID de la playlist « uploads » de la chaîne.
     */
    public function uploadsPlaylistId(string $channelId): ?string
    {
        $response = $this->get('channels', [
            'part' => 'contentDetails',
            'id' => $channelId,
        ]);

        if ($response === null) {
            return null;
        }

        return $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    }

    /**
     * @return list<array{id: string, title: string, description: string, publishedAt: string|null, thumbnailUrl: string, durationSeconds: int|null, liveBroadcastContent: string}>
     */
    public function videosByIds(array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }

        $chunks = array_chunk($videoIds, 50);
        $results = [];

        foreach ($chunks as $chunk) {
            $response = $this->get('videos', [
                'part' => 'snippet,contentDetails,liveStreamingDetails',
                'id' => implode(',', $chunk),
            ]);

            if ($response === null || ! is_array($response['items'] ?? null)) {
                continue;
            }

            foreach ($response['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $parsed = $this->parseVideoItem($item);
                if ($parsed !== null) {
                    $results[] = $parsed;
                }
            }
        }

        return $results;
    }

    /**
     * @return array{items: list<array{videoId: string, playlistId: string}>, nextPageToken: string|null}
     */
    public function playlistItemsPage(string $playlistId, int $maxResults = 50, ?string $pageToken = null): array
    {
        $params = [
            'part' => 'contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => min(50, max(1, $maxResults)),
        ];

        if ($pageToken !== null) {
            $params['pageToken'] = $pageToken;
        }

        $response = $this->get('playlistItems', $params);

        if ($response === null) {
            return ['items' => [], 'nextPageToken' => null];
        }

        $items = [];
        foreach ($response['items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $videoId = (string) ($row['contentDetails']['videoId'] ?? '');
            if ($videoId === '') {
                continue;
            }
            $items[] = [
                'videoId' => $videoId,
                'playlistId' => $playlistId,
            ];
        }

        $next = $response['nextPageToken'] ?? null;

        return [
            'items' => $items,
            'nextPageToken' => is_string($next) && $next !== '' ? $next : null,
        ];
    }

    /**
     * Parcourt toute une playlist (pagination interne).
     *
     * @return list<array{videoId: string, playlistId: string}>
     */
    public function allPlaylistItems(string $playlistId, int $limit = 500): array
    {
        $collected = [];
        $pageToken = null;

        do {
            $batch = $this->playlistItemsPage($playlistId, 50, $pageToken);
            $items = $batch['items'] ?? [];
            foreach ($items as $item) {
                $collected[] = $item;
                if (count($collected) >= $limit) {
                    return $collected;
                }
            }
            $pageToken = $batch['nextPageToken'] ?? null;
        } while ($pageToken !== null);

        return $collected;
    }

    /**
     * Parcourt une playlist du plus récent vers l’ancien et s’arrête après N vidéos déjà connues.
     *
     * @param  callable(string): bool  $videoAlreadyImported  True si la vidéo est déjà en base.
     * @return list<string> IDs vidéo potentiellement nouveaux (ordre playlist).
     */
    public function collectNewPlaylistVideoIds(
        string $playlistId,
        callable $videoAlreadyImported,
        int $maxScan = 200,
        int $stopAfterConsecutiveExisting = 8,
    ): array {
        $newIds = [];
        $consecutiveExisting = 0;
        $scanned = 0;
        $pageToken = null;

        do {
            $batch = $this->playlistItemsPage($playlistId, 50, $pageToken);
            foreach ($batch['items'] as $item) {
                $videoId = $item['videoId'];
                $scanned++;

                if ($videoAlreadyImported($videoId)) {
                    $consecutiveExisting++;
                    if ($consecutiveExisting >= $stopAfterConsecutiveExisting) {
                        return $newIds;
                    }
                } else {
                    $consecutiveExisting = 0;
                    $newIds[] = $videoId;
                }

                if ($scanned >= $maxScan) {
                    return $newIds;
                }
            }
            $pageToken = $batch['nextPageToken'] ?? null;
        } while ($pageToken !== null);

        return $newIds;
    }

    /**
     * Playlists publiques d'une chaîne (pagination YouTube).
     *
     * @return list<array{id: string, title: string, description: string, thumbnailUrl: string, itemCount: int, publishedAt: string|null}>
     */
    public function channelPlaylists(string $channelId, int $maxResults = 200): array
    {
        $collected = [];
        $pageToken = null;
        $remaining = max(1, $maxResults);

        do {
            $batchSize = min(50, $remaining);
            $params = [
                'part' => 'snippet,contentDetails',
                'channelId' => $channelId,
                'maxResults' => $batchSize,
            ];

            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->get('playlists', $params);

            if ($response === null) {
                break;
            }

            foreach ($response['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $parsed = $this->parsePlaylistItem($item);
                if ($parsed !== null) {
                    $collected[] = $parsed;
                    $remaining--;
                    if ($remaining <= 0) {
                        return $collected;
                    }
                }
            }

            $next = $response['nextPageToken'] ?? null;
            $pageToken = is_string($next) && $next !== '' ? $next : null;
        } while ($pageToken !== null);

        return $collected;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{id: string, title: string, description: string, thumbnailUrl: string, itemCount: int, publishedAt: string|null}|null
     */
    private function parsePlaylistItem(array $item): ?array
    {
        $id = (string) ($item['id'] ?? '');
        if ($id === '') {
            return null;
        }

        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $thumbs = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
        $thumb = $this->pickThumbnail($thumbs);
        $publishedAt = isset($snippet['publishedAt']) ? (string) $snippet['publishedAt'] : null;

        return [
            'id' => $id,
            'title' => (string) ($snippet['title'] ?? 'Playlist'),
            'description' => (string) ($snippet['description'] ?? ''),
            'thumbnailUrl' => $thumb,
            'itemCount' => (int) ($item['contentDetails']['itemCount'] ?? 0),
            'publishedAt' => $publishedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{id: string, title: string, description: string, publishedAt: string|null, thumbnailUrl: string, durationSeconds: int|null, liveBroadcastContent: string}|null
     */
    private function parseVideoItem(array $item): ?array
    {
        $id = (string) ($item['id'] ?? '');
        if ($id === '') {
            return null;
        }

        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $content = is_array($item['contentDetails'] ?? null) ? $item['contentDetails'] : [];
        $durationRaw = isset($content['duration']) ? (string) $content['duration'] : null;
        $durationSeconds = $durationRaw !== null
            ? YoutubeDurationParser::iso8601ToSeconds($durationRaw)
            : null;

        return [
            'id' => $id,
            'title' => (string) ($snippet['title'] ?? ''),
            'description' => (string) ($snippet['description'] ?? ''),
            'publishedAt' => isset($snippet['publishedAt']) ? (string) $snippet['publishedAt'] : null,
            'thumbnailUrl' => $this->pickThumbnail(is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : []),
            'durationSeconds' => $durationSeconds,
            'liveBroadcastContent' => (string) ($snippet['liveBroadcastContent'] ?? 'none'),
        ];
    }

    /**
     * @param  array<string, mixed>  $thumbnails
     */
    private function pickThumbnail(array $thumbnails): string
    {
        foreach (['maxres', 'high', 'medium', 'default'] as $size) {
            if (isset($thumbnails[$size]['url'])) {
                return (string) $thumbnails[$size]['url'];
            }
        }

        return '';
    }

    /**
     * @param  array<string, string|int>  $query
     * @return array<string, mixed>|null
     */
    private function get(string $endpoint, array $query): ?array
    {
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)->get("https://www.googleapis.com/youtube/v3/{$endpoint}", [
                ...$query,
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                Log::warning('[youtube-api] '.$endpoint, ['status' => $response->status(), 'body' => $response->json()]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $exception) {
            Log::warning('[youtube-api] '.$exception->getMessage());

            return null;
        }
    }
}
