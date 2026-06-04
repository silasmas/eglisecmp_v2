<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Models\Event;
use App\Models\Post;
use App\Support\YoutubeEventDateResolver;
use App\Support\YoutubePlaylistMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Synchronise vidéos, shorts et playlists YouTube vers posts / événements (enseignements).
 */
final class YoutubeChannelSyncService
{
    /** @var array<string, true> Vidéos déjà traitées durant la passe en cours (évite les doublons). */
    private array $processedVideoIds = [];

    public function __construct(
        private readonly YoutubeApiClient $api,
    ) {}

    /**
     * Lance la synchronisation complète.
     *
     * @param  bool  $dryRun  Si true, ne persiste pas en base.
     * @return array{ok: bool, message: string, playlists: int, videos: int, created: int, updated: int, skipped: int}
     */
    public function sync(bool $dryRun = false): array
    {
        $channelId = (string) config('site_public.youtube_channel_id', '');
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($channelId === '' || $apiKey === '') {
            return [
                'ok' => false,
                'message' => 'YOUTUBE_CHANNEL_ID ou YOUTUBE_API_KEY manquant dans .env',
                'playlists' => 0,
                'videos' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        $locale = (string) config('site_public.youtube_sync.default_locale', 'fr');
        $maxVideos = (int) config('site_public.youtube_sync.max_videos_per_run', 200);
        $importShorts = (bool) config('site_public.youtube_sync.import_shorts', true);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $playlistCount = 0;
        $this->processedVideoIds = [];

        $playlists = $this->api->channelPlaylists($channelId, 200);
        $playlistCount = count($playlists);

        if (! $dryRun) {
            foreach ($playlists as $playlist) {
                $this->upsertPlaylistAsEvent($playlist, $locale);
            }
        }

        $uploadsPlaylistId = $this->api->uploadsPlaylistId($channelId);

        if ($uploadsPlaylistId === null) {
            return [
                'ok' => false,
                'message' => 'Impossible de lire la playlist « uploads » de la chaîne (vérifiez l’ID et la clé API).',
                'playlists' => $playlistCount,
                'videos' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        $refs = $this->api->allPlaylistItems($uploadsPlaylistId, $maxVideos);
        $videoIds = array_values(array_unique(array_map(static fn (array $row): string => $row['videoId'], $refs)));

        $videos = $this->api->videosByIds($videoIds);
        $videoCount = count($videos);

        foreach ($videos as $video) {
            $kind = $this->resolveVideoKind($video, $importShorts);

            if ($kind === null) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                continue;
            }

            $result = $this->upsertVideoAsPost($video, $kind, $locale);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }
        }

        if (! $dryRun) {
            $this->importPlaylistVideos($playlists, $locale, $importShorts);
            $this->linkPlaylistMemberships($channelId, $playlists);
        }

        return [
            'ok' => true,
            'message' => $dryRun
                ? "Simulation OK : {$playlistCount} playlist(s), {$videoCount} vidéo(s) analysée(s)."
                : "Synchronisation terminée : {$created} créé(s), {$updated} mis à jour.",
            'playlists' => $playlistCount,
            'videos' => $videoCount,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array{id: string, title: string, description: string, thumbnailUrl: string, itemCount?: int, publishedAt?: string|null}  $playlist
     */
    private function upsertPlaylistAsEvent(array $playlist, string $locale): Event
    {
        $existing = Event::query()->where('youtube_playlist_id', $playlist['id'])->first();

        $designation = [$locale => $playlist['title']];
        $description = $playlist['description'] !== ''
            ? [$locale => $playlist['description']]
            : null;

        [$dateDebut, $dateFin] = YoutubeEventDateResolver::resolveRange([
            'title' => $playlist['title'],
            'publishedAt' => $playlist['publishedAt'] ?? null,
        ]);

        $publishedAt = isset($playlist['publishedAt']) && is_string($playlist['publishedAt'])
            ? Carbon::parse($playlist['publishedAt'])
            : null;

        $payload = [
            'designation' => $designation,
            'description' => $description,
            'youtube_playlist_id' => $playlist['id'],
            'youtube_playlist_item_count' => (int) ($playlist['itemCount'] ?? 0),
            'youtube_published_at' => $publishedAt,
            'is_active' => true,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ];

        if ($playlist['thumbnailUrl'] !== '') {
            $payload['image_url'] = [$locale => $playlist['thumbnailUrl']];
        }

        if ($existing !== null) {
            $existing->update($payload);

            return $existing->fresh() ?? $existing;
        }

        return Event::query()->create($payload);
    }

    /**
     * Importe les vidéos de chaque playlist YouTube (au-delà de la limite « uploads »).
     *
     * @param  list<array{id: string, title: string, description: string, thumbnailUrl: string, itemCount?: int, publishedAt?: string|null}>  $playlists
     */
    private function importPlaylistVideos(array $playlists, string $locale, bool $importShorts): void
    {
        $perPlaylistLimit = (int) config('site_public.youtube_sync.max_playlist_videos_per_run', 120);

        foreach ($playlists as $playlist) {
            $members = $this->api->allPlaylistItems($playlist['id'], $perPlaylistLimit);
            if ($members === []) {
                continue;
            }

            $videoIds = array_values(array_unique(array_map(
                static fn (array $row): string => $row['videoId'],
                $members
            )));

            $videos = $this->api->videosByIds($videoIds);
            foreach ($videos as $video) {
                $kind = $this->resolveVideoKind($video, $importShorts);
                if ($kind === null) {
                    continue;
                }
                $this->upsertVideoAsPost($video, $kind, $locale);
            }
        }
    }

    /**
     * Associe chaque vidéo aux playlists YouTube (onglet Playlists = event_id).
     *
     * @param  list<array{id: string, title: string, description: string, thumbnailUrl: string, itemCount: int}>  $playlists
     */
    private function linkPlaylistMemberships(string $channelId, array $playlists): void
    {
        foreach ($playlists as $playlist) {
            $event = Event::query()->where('youtube_playlist_id', $playlist['id'])->first();
            if ($event === null) {
                continue;
            }

            $limit = (int) config('site_public.youtube_sync.max_playlist_videos_per_run', 120);
            $members = $this->api->allPlaylistItems($playlist['id'], $limit);
            $meditationGroup = YoutubePlaylistMatcher::meditationGroupForTitle($playlist['title']);
            $weeklyDay = $meditationGroup !== null
                ? YoutubePlaylistMatcher::weeklyServiceDayForGroup($meditationGroup)
                : null;

            foreach ($members as $member) {
                $update = [
                    'event_id' => $event->id,
                    'youtube_playlist_id' => $playlist['id'],
                ];
                if ($weeklyDay !== null) {
                    $update['weekly_service_day'] = $weeklyDay;
                }
                Post::query()
                    ->where('youtube_video_id', $member['videoId'])
                    ->update($update);
            }
        }
    }

    /**
     * @param  array{id: string, title: string, description: string, publishedAt: string|null, thumbnailUrl: string, durationSeconds: int|null, liveBroadcastContent: string}  $video
     */
    private function upsertVideoAsPost(array $video, string $kind, string $locale): string
    {
        $videoId = trim($video['id']);
        if ($videoId === '') {
            return 'skipped';
        }

        if (isset($this->processedVideoIds[$videoId])) {
            return 'updated';
        }

        $post = $this->findPostForYoutubeVideo($videoId);

        $linkUrl = 'https://www.youtube.com/watch?v='.$videoId;
        $title = [$locale => $video['title']];
        $body = $video['description'] !== '' ? [$locale => $video['description']] : null;
        $publishedAt = $video['publishedAt'] !== null
            ? Carbon::parse($video['publishedAt'])
            : now();

        $defaultAuthor = (string) config('site_public.default_speaker_name', 'Centre Missionnaire Philadelphie');

        $payload = [
            'title' => $title,
            'type' => 1,
            'author' => $defaultAuthor,
            'link_url' => $linkUrl,
            'youtube_video_id' => $videoId,
            'youtube_kind' => $kind,
            'youtube_synced_at' => now(),
            'date_publication' => $publishedAt,
            'is_active' => true,
            'youtube_duration_seconds' => $video['durationSeconds'],
        ];

        if ($body !== null) {
            $payload['body'] = $body;
            $payload['observation'] = [$locale => Str::limit(strip_tags($video['description']), 500)];
        }

        if ($video['thumbnailUrl'] !== '') {
            $payload['image_url'] = [$locale => $video['thumbnailUrl']];
        }

        $payload['references'] = [$locale => 'youtube:'.$videoId];

        if ($post === null) {
            $payload['slug'] = $this->uniqueSlug($video['title'], $videoId);

            try {
                Post::query()->create($payload);
                $this->processedVideoIds[$videoId] = true;

                return 'created';
            } catch (UniqueConstraintViolationException $exception) {
                $existing = Post::query()->where('youtube_video_id', $videoId)->first();
                if ($existing === null) {
                    throw $exception;
                }
                $existing->update($payload);
                $this->processedVideoIds[$videoId] = true;

                return 'updated';
            }
        }

        $post->update($payload);
        $this->processedVideoIds[$videoId] = true;

        return 'updated';
    }

    /**
     * Retrouve une publication existante par ID YouTube ou par lien legacy (sans youtube_video_id).
     */
    private function findPostForYoutubeVideo(string $videoId): ?Post
    {
        $byId = Post::query()->where('youtube_video_id', $videoId)->first();
        if ($byId !== null) {
            return $byId;
        }

        $watchUrl = 'https://www.youtube.com/watch?v='.$videoId;
        $shortUrl = 'https://youtu.be/'.$videoId;

        return Post::query()
            ->where(function (Builder $query) use ($videoId, $watchUrl, $shortUrl): void {
                $query->where('link_url', $watchUrl)
                    ->orWhere('link_url', $shortUrl)
                    ->orWhere('link_url', 'like', '%'.$videoId.'%')
                    ->orWhere('references', 'like', '%'.$videoId.'%');
            })
            ->orderByRaw('CASE WHEN youtube_video_id IS NOT NULL AND youtube_video_id != "" THEN 0 ELSE 1 END')
            ->orderByDesc('date_publication')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array{id: string, title: string, description: string, publishedAt: string|null, thumbnailUrl: string, durationSeconds: int|null, liveBroadcastContent: string}  $video
     */
    private function resolveVideoKind(array $video, bool $importShorts): ?string
    {
        $live = $video['liveBroadcastContent'] ?? 'none';
        if ($live === 'live') {
            return 'live';
        }
        if ($live === 'upcoming') {
            return 'live_upcoming';
        }

        $isShort = ($video['durationSeconds'] !== null && $video['durationSeconds'] <= 60)
            || str_contains(strtolower($video['title']), '#short');

        if ($isShort && ! $importShorts) {
            return null;
        }

        return $isShort ? 'short' : 'video';
    }

    private function uniqueSlug(string $title, string $videoId): string
    {
        $base = Str::slug(Str::limit($title, 80, ''));
        if ($base === '') {
            $base = 'video-'.$videoId;
        }

        $slug = $base;
        $suffix = 0;
        while (Post::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
