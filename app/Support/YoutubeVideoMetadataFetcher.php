<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Récupère la durée d'une vidéo via l'API YouTube Data v3 (« videos », part « contentDetails »).
 */
final class YoutubeVideoMetadataFetcher
{
    /**
     * Retourne la durée en secondes pour un ID vidéo, ou null (clé API absente, erreur réseau ou ID invalide).
     *
     * @param  string  $videoId  Identifiant 11 caractères.
     */
    public static function durationSeconds(?string $videoId): ?int
    {
        if ($videoId === null || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            return null;
        }

        $apiKey = (string) config('services.youtube.api_key', '');

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'contentDetails',
                    'id' => $videoId,
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                return null;
            }

            /** @var string|null $durationRaw */
            $durationRaw = $response->json('items.0.contentDetails.duration');

            return YoutubeDurationParser::iso8601ToSeconds($durationRaw);

        } catch (\Throwable $e) {
            Log::debug('[youtube-metadata] '.$e->getMessage());

            return null;
        }
    }
}
