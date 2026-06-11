<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Post;
use App\Support\EventPostQuery;
use App\Support\SitePublicSerializer;
use App\Support\WeeklyMeditationGrouper;
use App\Support\YoutubeEventDateResolver;
use App\Support\YoutubePlaylistMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Playlists YouTube regroupées pour la page Enseignements (méditations / playlists).
 */
final class PublicTeachingsPlaylistController extends Controller
{
    /**
     * Groupes cultes hebdomadaires (Mercredi, Jeudi, Dimanche).
     *
     * @return JsonResponse `{ data: TeachingsPlaylistGroup[] }`
     */
    public function meditations(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        return response()->json([
            'data' => $this->buildMeditationGroups($locale, $fallback),
        ]);
    }

    /**
     * Toutes les playlists YouTube sauf les cultes hebdomadaires.
     *
     * @return JsonResponse `{ data: TeachingsPlaylistGroup[] }`
     */
    public function playlists(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 15), 1), 48);

        $playlistEvents = $this->resolvePublicPlaylistEvents($locale, $fallback);
        $total = count($playlistEvents);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageEvents = array_slice($playlistEvents, $offset, $perPage);

        $groups = [];
        foreach ($pageEvents as $event) {
            $group = $this->eventToGroup($event, $locale, $fallback, listMode: true);
            if ($this->shouldExposePlaylistGroup($group)) {
                $groups[] = $group;
            }
        }

        usort($groups, static fn (array $a, array $b): int => strcmp((string) ($b['sortDate'] ?? ''), (string) ($a['sortDate'] ?? '')));

        return response()->json([
            'data' => $this->stripSortDate($groups),
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $page < $lastPage,
            ],
        ]);
    }

    /**
     * Événements playlist publics (hors cultes hebdomadaires), du plus récent au plus ancien.
     *
     * @return list<Event>
     */
    private function resolvePublicPlaylistEvents(string $locale, string $fallback): array
    {
        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('youtube_playlist_id')
            ->orderByDesc('youtube_published_at')
            ->orderByDesc('date_debut')
            ->get();

        $playlistEvents = [];
        foreach ($events as $event) {
            $title = SitePublicSerializer::text($event->designation, $locale, $fallback);
            if (YoutubePlaylistMatcher::meditationGroupForTitle($title) !== null) {
                continue;
            }

            if ((int) ($event->youtube_playlist_item_count ?? 0) <= 0
                && EventPostQuery::activeCountForEvent($event) <= 0) {
                continue;
            }

            $playlistEvents[] = $event;
        }

        return $playlistEvents;
    }

    /**
     * Détail d'une playlist (messages + identifiant YouTube pour repli embed).
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $group = $this->eventToGroup($event, $locale, $fallback, listMode: false);
        unset($group['sortDate']);

        $meditationLabel = YoutubePlaylistMatcher::meditationGroupForTitle(
            SitePublicSerializer::text($event->designation, $locale, $fallback)
        );
        if ($meditationLabel !== null) {
            $weeklyDay = YoutubePlaylistMatcher::weeklyServiceDayForGroup($meditationLabel);
            if ($weeklyDay !== null) {
                $group['weeklyServiceDay'] = $weeklyDay;
            }
        }

        return response()->json([
            'data' => array_merge($group, [
                'youtubePlaylistId' => is_string($event->youtube_playlist_id) ? $event->youtube_playlist_id : null,
            ]),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMeditationGroups(string $locale, string $fallback): array
    {
        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('youtube_playlist_id')
            ->get();

        return WeeklyMeditationGrouper::buildGroups($locale, $fallback, $events);
    }

    /**
     * Horodatage de la dernière vidéo liée à l’événement (tri décroissant).
     */
    private function latestActivityTimestamp(Event $event): string
    {
        $latestPost = EventPostQuery::latestPostForEvent($event);
        if ($latestPost instanceof Post) {
            $stamp = SitePublicSerializer::postSortTimestamp($latestPost);
            if ($stamp !== '') {
                return $stamp;
            }
        }

        return YoutubeEventDateResolver::sortTimestamp($event);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventToGroup(Event $event, string $locale, string $fallback, bool $listMode): array
    {
        $title = SitePublicSerializer::text($event->designation, $locale, $fallback);
        $description = SitePublicSerializer::text($event->description, $locale, $fallback);
        $thumb = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallback);
        if ($thumb === '') {
            $thumb = (string) config('site_public.placeholder_image_url', '');
        }

        if ($listMode) {
            $latestPost = EventPostQuery::latestPostForEvent($event);
            $posts = $latestPost instanceof Post ? collect([$latestPost]) : collect();
        } else {
            $posts = collect(EventPostQuery::newestPostsForEvent($event));
        }

        $items = $posts->map(
            static fn (Post $post): array => SitePublicSerializer::postToSermonArray($post, $locale, $fallback)
        )->values()->all();

        $latestItem = $items[0] ?? null;

        if (is_array($latestItem) && is_string($latestItem['thumbnail'] ?? null) && $latestItem['thumbnail'] !== '') {
            $thumb = $latestItem['thumbnail'];
        }

        $syncedCount = $listMode
            ? EventPostQuery::activeCountForEvent($event)
            : count($items);
        $youtubeCount = (int) ($event->youtube_playlist_item_count ?? 0);
        $videoCount = max($youtubeCount, $syncedCount);

        $sortDate = $this->latestActivityTimestamp($event);

        $group = [
            'eventId' => (string) $event->id,
            'title' => $title,
            'description' => $description,
            'thumbnail' => $thumb,
            'videoCount' => $videoCount,
            'syncedCount' => $syncedCount,
            'visibility' => 'Publique',
            'items' => $items,
            'sortDate' => $sortDate,
        ];

        if ($latestItem !== null) {
            $group['latestItem'] = $latestItem;
        }

        return $group;
    }

    /**
     * Affiche la playlist si YouTube indique au moins une vidéo ou si des messages sont synchronisés.
     *
     * @param  array<string, mixed>  $group
     */
    private function shouldExposePlaylistGroup(array $group): bool
    {
        return (int) ($group['videoCount'] ?? 0) > 0;
    }

    /**
     * Retire le champ interne de tri avant envoi JSON.
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function stripSortDate(array $groups): array
    {
        return array_map(static function (array $group): array {
            unset($group['sortDate']);

            return $group;
        }, $groups);
    }
}
