<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Models\Event;
use App\Models\Post;
use App\Support\EventPostQuery;
use App\Support\YoutubeEventDateResolver;
use App\Support\YoutubePlaylistMatcher;
use App\Support\YoutubeSyncState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Synchronise vidéos, shorts et playlists YouTube vers posts / événements (enseignements).
 * Mode incrémental par défaut : reprend depuis la dernière synchro (compteurs playlists + uploads récents).
 */
final class YoutubeChannelSyncService
{
    /** @var array<string, true> Vidéos déjà traitées durant la passe en cours (évite les doublons). */
    private array $processedVideoIds = [];

    public function __construct(
        private readonly YoutubeApiClient $api,
    ) {}

    /**
     * Lance la synchronisation (incrémentale sauf si $full).
     *
     * @param  bool  $dryRun  Si true, ne persiste pas en base.
     * @param  bool  $full  Si true, ignore l’état mémorisé et parcourt tout comme avant.
     * @return array{ok: bool, message: string, playlists: int, videos: int, created: int, updated: int, unchanged: int, skipped: int}
     */
    public function sync(bool $dryRun = false, bool $full = false): array
    {
        $channelId = (string) config('site_public.youtube_channel_id', '');
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($channelId === '' || $apiKey === '') {
            return $this->failureResult('YOUTUBE_CHANNEL_ID ou YOUTUBE_API_KEY manquant dans .env');
        }

        $locale = (string) config('site_public.youtube_sync.default_locale', 'fr');
        $maxVideos = (int) config('site_public.youtube_sync.max_videos_per_run', 200);
        $importShorts = (bool) config('site_public.youtube_sync.import_shorts', true);
        $stopAfterExisting = (int) config('site_public.youtube_sync.incremental_stop_after_existing', 8);

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
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
            return $this->failureResult(
                'Impossible de lire la playlist « uploads » de la chaîne (vérifiez l’ID et la clé API).',
                $playlistCount,
            );
        }

        $alreadyImported = fn (string $videoId): bool => Post::query()
            ->where('youtube_video_id', $videoId)
            ->exists();

        if ($full) {
            $refs = $this->api->allPlaylistItems($uploadsPlaylistId, $maxVideos);
            $videoIds = array_values(array_unique(array_map(
                static fn (array $row): string => $row['videoId'],
                $refs
            )));
        } else {
            $videoIds = $this->api->collectNewPlaylistVideoIds(
                $uploadsPlaylistId,
                $alreadyImported,
                $maxVideos,
                $stopAfterExisting,
            );
        }

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
            } elseif ($result === 'unchanged') {
                $unchanged++;
            } else {
                $skipped++;
            }
        }

        if (! $dryRun) {
            $this->importPlaylistVideos($playlists, $locale, $importShorts, $full, $stopAfterExisting);
            $this->linkPlaylistMemberships($channelId, $playlists, $full);
            $this->refreshPlaylistEventThumbnails($locale);
            $this->persistSyncState($playlists);
        }

        $message = $this->buildResultMessage(
            $dryRun,
            $full,
            $playlistCount,
            $videoCount,
            $created,
            $updated,
            $unchanged,
        );

        return [
            'ok' => true,
            'message' => $message,
            'playlists' => $playlistCount,
            'videos' => $videoCount,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<array{id: string, title: string, description: string, thumbnailUrl: string, itemCount?: int, publishedAt?: string|null}>  $playlists
     */
    private function persistSyncState(array $playlists): void
    {
        $counts = [];
        foreach ($playlists as $playlist) {
            $counts[$playlist['id']] = [
                'itemCount' => (int) ($playlist['itemCount'] ?? 0),
            ];
        }

        YoutubeSyncState::save($counts);
    }

    /**
     * Met à jour la vignette des événements-playlist avec la dernière vidéo synchronisée.
     */
    private function refreshPlaylistEventThumbnails(string $locale): void
    {
        $events = Event::query()
            ->whereNotNull('youtube_playlist_id')
            ->get();

        foreach ($events as $event) {
            $latest = EventPostQuery::latestPostForEvent($event);
            if ($latest === null) {
                continue;
            }

            $thumb = is_array($latest->image_url)
                ? (string) ($latest->image_url[$locale] ?? reset($latest->image_url) ?: '')
                : '';

            if ($thumb === '') {
                continue;
            }

            $imageUrl = is_array($event->image_url) ? $event->image_url : [];
            if (($imageUrl[$locale] ?? '') === $thumb) {
                continue;
            }

            $imageUrl[$locale] = $thumb;
            $event->update(['image_url' => $imageUrl]);
        }
    }

    private function buildResultMessage(
        bool $dryRun,
        bool $full,
        int $playlistCount,
        int $videoCount,
        int $created,
        int $updated,
        int $unchanged,
    ): string {
        if ($dryRun) {
            return "Simulation OK : {$playlistCount} playlist(s), {$videoCount} vidéo(s) analysée(s).";
        }

        if ($created === 0 && $updated === 0) {
            $last = YoutubeSyncState::lastCompletedAt();
            $hint = $last !== null
                ? ' (dernière synchro : '.$last->locale('fr')->isoFormat('D MMM YYYY à HH:mm').')'
                : '';

            if ($unchanged > 0 && ! $full) {
                return 'Aucun nouveau contenu YouTube'.$hint.'. '.$unchanged.' vidéo(s) déjà à jour.';
            }

            return 'Aucun nouveau contenu YouTube'.$hint.'.';
        }

        $mode = $full ? 'complète' : 'incrémentale';

        return "Synchronisation {$mode} terminée : {$created} créé(s), {$updated} mis à jour.";
    }

    /**
     * @return array{ok: bool, message: string, playlists: int, videos: int, created: int, updated: int, unchanged: int, skipped: int}
     */
    private function failureResult(string $message, int $playlists = 0): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'playlists' => $playlists,
            'videos' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
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
     * Importe les vidéos des playlists dont le nombre d’éléments a changé (ou tout si $full).
     *
     * @param  list<array{id: string, title: string, description: string, thumbnailUrl: string, itemCount?: int, publishedAt?: string|null}>  $playlists
     */
    private function importPlaylistVideos(
        array $playlists,
        string $locale,
        bool $importShorts,
        bool $full,
        int $stopAfterExisting,
    ): void {
        $perPlaylistLimit = (int) config('site_public.youtube_sync.max_playlist_videos_per_run', 120);
        $alreadyImported = fn (string $videoId): bool => Post::query()
            ->where('youtube_video_id', $videoId)
            ->exists();

        foreach ($playlists as $playlist) {
            $playlistId = $playlist['id'];
            $newCount = (int) ($playlist['itemCount'] ?? 0);

            if (! $full) {
                $prevCount = YoutubeSyncState::previousPlaylistItemCount($playlistId);
                if ($prevCount !== null && $prevCount === $newCount) {
                    continue;
                }
            }

            if ($full) {
                $members = $this->api->allPlaylistItems($playlistId, $perPlaylistLimit);
                $videoIds = array_values(array_unique(array_map(
                    static fn (array $row): string => $row['videoId'],
                    $members
                )));
            } else {
                $videoIds = $this->api->collectNewPlaylistVideoIds(
                    $playlistId,
                    $alreadyImported,
                    $perPlaylistLimit,
                    $stopAfterExisting,
                );
            }

            if ($videoIds === []) {
                continue;
            }

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
    private function linkPlaylistMemberships(string $channelId, array $playlists, bool $full): void
    {
        foreach ($playlists as $playlist) {
            if (! $full) {
                $prevCount = YoutubeSyncState::previousPlaylistItemCount($playlist['id']);
                $newCount = (int) ($playlist['itemCount'] ?? 0);
                if ($prevCount !== null && $prevCount === $newCount) {
                    continue;
                }
            }

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
            return 'unchanged';
        }

        $post = $this->findPostForYoutubeVideo($videoId);

        $linkUrl = 'https://www.youtube.com/watch?v='.$videoId;
        $title = [$locale => $video['title']];
        $body = $video['description'] !== '' ? [$locale => $video['description']] : null;
        $publishedAt = $video['publishedAt'] !== null
            ? Carbon::parse($video['publishedAt'])
            : now();

        if ($post !== null && $this->postMatchesYoutubeSnapshot($post, $video, $locale, $publishedAt, $kind)) {
            $this->processedVideoIds[$videoId] = true;

            return 'unchanged';
        }

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
     * Vérifie si le post local reflète déjà la fiche YouTube (évite des UPDATE inutiles).
     */
    private function postMatchesYoutubeSnapshot(
        Post $post,
        array $video,
        string $locale,
        Carbon $publishedAt,
        string $kind,
    ): bool {
        $localTitle = is_array($post->title) ? (string) ($post->title[$locale] ?? reset($post->title) ?: '') : '';
        $localThumb = is_array($post->image_url)
            ? (string) ($post->image_url[$locale] ?? reset($post->image_url) ?: '')
            : '';

        $sameDate = $post->date_publication !== null
            && $post->date_publication->equalTo($publishedAt);

        return $localTitle === $video['title']
            && $sameDate
            && $localThumb === $video['thumbnailUrl']
            && (string) $post->youtube_kind === $kind
            && (int) ($post->youtube_duration_seconds ?? 0) === (int) ($video['durationSeconds'] ?? 0);
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
