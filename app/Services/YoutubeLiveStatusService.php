<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Détecte si la chaîne YouTube diffuse un live.
 *
 * Les endpoints publics ne lisent que le cache (pas d’appel HTTP bloquant).
 * Le rafraîchissement se fait via `youtube:check-live` / `snapshot(true)`.
 */
final class YoutubeLiveStatusService
{
    private const CACHE_KEY = 'site.youtube_live_status';

    private const CACHE_SECONDS_LIVE = 45;

    private const CACHE_SECONDS_IDLE = 60;

    private const STALE_LIVE_GRACE_SECONDS = 300;

    private const HTTP_TIMEOUT_SECONDS = 3;

    /**
     * Statut live pour le site public : lecture cache uniquement (jamais de réseau).
     *
     * @return array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}|null
     */
    public function current(): ?array
    {
        $cached = $this->readCache();

        if ($cached === null) {
            return null;
        }

        return $cached['isLive'] === true ? $cached['live'] : null;
    }

    /**
     * Interroge YouTube et met à jour le cache (CLI / scheduler uniquement).
     *
     * @return array{isLive: bool, live: array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}|null}
     */
    public function snapshot(bool $bypassCache = true): array
    {
        $channelId = (string) config('site_public.youtube_channel_id', '');
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($channelId === '' || $apiKey === '') {
            return ['isLive' => false, 'live' => null];
        }

        if (! $bypassCache) {
            $cached = $this->readCache();

            if ($cached !== null) {
                return $cached;
            }
        }

        $fetchResult = $this->fetchFromApi($channelId, $apiKey);

        if ($fetchResult === null) {
            return $this->fallbackOnApiFailure();
        }

        $isLive = ($fetchResult['isLive'] ?? false) === true;
        $ttl = $isLive ? self::CACHE_SECONDS_LIVE : self::CACHE_SECONDS_IDLE;

        Cache::put(self::CACHE_KEY, array_merge($fetchResult, [
            'cachedAt' => now()->toIso8601String(),
        ]), $ttl);

        return [
            'isLive' => $isLive,
            'live' => $isLive ? $this->normalizePayload($fetchResult) : null,
        ];
    }

    /**
     * @return array{isLive: bool, live: array<string, mixed>|null}|null
     */
    private function readCache(): ?array
    {
        /** @var array<string, mixed>|null $raw */
        $raw = Cache::get(self::CACHE_KEY);

        if (! is_array($raw)) {
            return null;
        }

        $isLive = ($raw['isLive'] ?? false) === true;

        return [
            'isLive' => $isLive,
            'live' => $isLive ? $this->normalizePayload($raw) : null,
        ];
    }

    /**
     * Conserve le dernier live connu si l’API échoue.
     *
     * @return array{isLive: bool, live: array<string, mixed>|null}
     */
    private function fallbackOnApiFailure(): array
    {
        /** @var array<string, mixed>|null $raw */
        $raw = Cache::get(self::CACHE_KEY);

        if (is_array($raw) && ($raw['isLive'] ?? false) === true) {
            $cachedAt = isset($raw['cachedAt']) ? strtotime((string) $raw['cachedAt']) : false;
            $freshEnough = $cachedAt !== false && (time() - $cachedAt) <= self::STALE_LIVE_GRACE_SECONDS;

            if ($freshEnough) {
                Log::info('YouTube live: repli cache (API indisponible)');

                return [
                    'isLive' => true,
                    'live' => $this->normalizePayload($raw),
                ];
            }
        }

        Cache::put(self::CACHE_KEY, [
            'isLive' => false,
            'cachedAt' => now()->toIso8601String(),
        ], self::CACHE_SECONDS_IDLE);

        return ['isLive' => false, 'live' => null];
    }

    /**
     * @return array<string, mixed>|null Null = échec API (ne pas écraser un live frais).
     */
    private function fetchFromApi(string $channelId, string $apiKey): ?array
    {
        try {
            $fromSearch = $this->fetchLiveViaSearch($channelId, $apiKey);
            if ($fromSearch !== null) {
                return $fromSearch;
            }

            $fromRecent = $this->fetchLiveViaRecentVideos($channelId, $apiKey);
            if ($fromRecent !== null) {
                return $fromRecent;
            }

            return ['isLive' => false];
        } catch (\Throwable $exception) {
            Log::warning('YouTube live fetch failed', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Client HTTP court pour ne pas bloquer le scheduler.
     */
    private function youtubeHttp(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(self::HTTP_TIMEOUT_SECONDS)
            ->connectTimeout(2)
            ->retry(0);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLiveViaSearch(string $channelId, string $apiKey): ?array
    {
        $response = $this->youtubeHttp()->get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'snippet',
            'channelId' => $channelId,
            'eventType' => 'live',
            'type' => 'video',
            'maxResults' => 1,
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            Log::warning('YouTube live search API error', [
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 400),
            ]);

            return null;
        }

        $items = $response->json('items', []);
        if (! is_array($items) || $items === []) {
            return null;
        }

        $item = $items[0];
        if (! is_array($item)) {
            return null;
        }

        $videoId = (string) ($item['id']['videoId'] ?? '');
        if ($videoId === '') {
            return null;
        }

        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];

        return $this->buildLivePayload($videoId, $snippet);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLiveViaRecentVideos(string $channelId, string $apiKey): ?array
    {
        $searchResponse = $this->youtubeHttp()->get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'snippet',
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => 5,
            'key' => $apiKey,
        ]);

        if (! $searchResponse->successful()) {
            return null;
        }

        $items = $searchResponse->json('items', []);
        if (! is_array($items) || $items === []) {
            return null;
        }

        $videoIds = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (string) ($item['id']['videoId'] ?? '');
            if ($id !== '') {
                $videoIds[] = $id;
            }
        }

        if ($videoIds === []) {
            return null;
        }

        $videosResponse = $this->youtubeHttp()->get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,liveStreamingDetails',
            'id' => implode(',', $videoIds),
            'key' => $apiKey,
        ]);

        if (! $videosResponse->successful()) {
            return null;
        }

        $videos = $videosResponse->json('items', []);
        if (! is_array($videos)) {
            return null;
        }

        foreach ($videos as $video) {
            if (! is_array($video)) {
                continue;
            }

            $snippet = is_array($video['snippet'] ?? null) ? $video['snippet'] : [];
            $broadcast = (string) ($snippet['liveBroadcastContent'] ?? 'none');

            if ($broadcast !== 'live') {
                continue;
            }

            $videoId = (string) ($video['id'] ?? '');
            if ($videoId === '') {
                continue;
            }

            return $this->buildLivePayload($videoId, $snippet);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $snippet
     * @return array<string, mixed>
     */
    private function buildLivePayload(string $videoId, array $snippet): array
    {
        $title = (string) ($snippet['title'] ?? 'Live en cours');
        $thumbs = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
        $thumb = '';

        foreach (['maxres', 'high', 'medium', 'default'] as $size) {
            if (isset($thumbs[$size]['url'])) {
                $thumb = (string) $thumbs[$size]['url'];
                break;
            }
        }

        return [
            'isLive' => true,
            'videoId' => $videoId,
            'title' => $title,
            'embedUrl' => 'https://www.youtube.com/embed/'.$videoId.'?autoplay=1&rel=0&modestbranding=1',
            'thumbnailUrl' => $thumb,
            'watchUrl' => 'https://www.youtube.com/watch?v='.$videoId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}
     */
    private function normalizePayload(array $payload): array
    {
        return [
            'isLive' => true,
            'videoId' => (string) ($payload['videoId'] ?? ''),
            'title' => (string) ($payload['title'] ?? 'Live en cours'),
            'embedUrl' => (string) ($payload['embedUrl'] ?? ''),
            'thumbnailUrl' => (string) ($payload['thumbnailUrl'] ?? ''),
            'watchUrl' => (string) ($payload['watchUrl'] ?? ''),
        ];
    }
}
