<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Détecte si la chaîne YouTube configurée diffuse un live (API Data v3).
 */
final class YoutubeLiveStatusService
{
    private const CACHE_KEY = 'site.youtube_live_status';

    private const CACHE_SECONDS = 90;

    /**
     * Retourne les infos du live en cours ou null si aucun / API indisponible.
     *
     * @return array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}|null
     */
    public function current(): ?array
    {
        $channelId = (string) config('site_public.youtube_channel_id', '');
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($channelId === '' || $apiKey === '') {
            return null;
        }

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached['isLive'] === true ? $this->normalizePayload($cached) : null;
        }

        $payload = $this->fetchFromApi($channelId, $apiKey);
        Cache::put(self::CACHE_KEY, $payload ?? ['isLive' => false], self::CACHE_SECONDS);

        return $payload !== null && ($payload['isLive'] ?? false) === true
            ? $this->normalizePayload($payload)
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchFromApi(string $channelId, string $apiKey): ?array
    {
        try {
            $response = Http::timeout(8)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'snippet',
                'channelId' => $channelId,
                'eventType' => 'live',
                'type' => 'video',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                Log::warning('YouTube live API error', ['status' => $response->status()]);

                return ['isLive' => false];
            }

            $items = $response->json('items', []);
            if (! is_array($items) || $items === []) {
                return ['isLive' => false];
            }

            $item = $items[0];
            $videoId = is_array($item) ? (string) ($item['id']['videoId'] ?? '') : '';
            if ($videoId === '') {
                return ['isLive' => false];
            }

            $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
            $title = (string) ($snippet['title'] ?? 'Live en cours');
            $thumbs = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
            $thumb = '';
            foreach (['high', 'medium', 'default'] as $size) {
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
        } catch (\Throwable $exception) {
            Log::warning('YouTube live fetch failed', ['message' => $exception->getMessage()]);

            return ['isLive' => false];
        }
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
