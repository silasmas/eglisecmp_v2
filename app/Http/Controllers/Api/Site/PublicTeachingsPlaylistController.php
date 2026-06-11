<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Post;
use App\Support\EventPostQuery;
use App\Support\SitePublicSerializer;
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

        $groups = [];

        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('youtube_playlist_id')
            ->orderByDesc('youtube_published_at')
            ->orderByDesc('date_debut')
            ->get();

        foreach ($events as $event) {
            $title = SitePublicSerializer::text($event->designation, $locale, $fallback);
            if (YoutubePlaylistMatcher::meditationGroupForTitle($title) !== null) {
                continue;
            }

            $group = $this->eventToGroup($event, $locale, $fallback);
            if ($this->shouldExposePlaylistGroup($group)) {
                $groups[] = $group;
            }
        }

        usort($groups, static fn (array $a, array $b): int => strcmp((string) ($b['sortDate'] ?? ''), (string) ($a['sortDate'] ?? '')));

        return response()->json(['data' => $this->stripSortDate($groups)]);
    }

    /**
     * Détail d'une playlist (messages + identifiant YouTube pour repli embed).
     *
     * @return JsonResponse
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $group = $this->eventToGroup($event, $locale, $fallback);
        unset($group['sortDate']);

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
        $groups = [];
        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('youtube_playlist_id')
            ->get();

        foreach (YoutubePlaylistMatcher::meditationGroups() as $config) {
            $label = (string) ($config['label'] ?? '');
            $event = null;

            foreach ($events as $candidate) {
                $title = SitePublicSerializer::text($candidate->designation, $locale, $fallback);
                if (YoutubePlaylistMatcher::meditationGroupForTitle($title) === $label) {
                    $event = $candidate;
                    break;
                }
            }

            if ($event === null) {
                continue;
            }

            $group = $this->eventToGroup($event, $locale, $fallback);
            if ($this->shouldExposePlaylistGroup($group)) {
                $groups[] = $group;
            }
        }

        usort($groups, static fn (array $a, array $b): int => strcmp((string) ($b['sortDate'] ?? ''), (string) ($a['sortDate'] ?? '')));

        return $this->stripSortDate($groups);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventToGroup(Event $event, string $locale, string $fallback): array
    {
        $title = SitePublicSerializer::text($event->designation, $locale, $fallback);
        $description = SitePublicSerializer::text($event->description, $locale, $fallback);
        $thumb = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallback);
        if ($thumb === '') {
            $thumb = (string) config('site_public.placeholder_image_url', '');
        }

        $posts = Post::query()
            ->where('is_active', true)
            ->where(function ($query) use ($event): void {
                EventPostQuery::applyForEvent($query, $event);
            })
            ->orderByDesc('date_publication')
            ->orderByDesc('id')
            ->with(['minister', 'event'])
            ->get();

        $items = $posts->map(
            static fn (Post $post): array => SitePublicSerializer::postToSermonArray($post, $locale, $fallback)
        )->values()->all();

        $latestPost = $posts->first();
        if ($latestPost instanceof Post) {
            $latestThumb = SitePublicSerializer::imageUrl($latestPost->image_url, $locale, $fallback);
            if ($latestThumb !== '') {
                $thumb = $latestThumb;
            }
        }

        $syncedCount = count($items);
        $youtubeCount = (int) ($event->youtube_playlist_item_count ?? 0);
        $videoCount = max($youtubeCount, $syncedCount);

        $sortDate = YoutubeEventDateResolver::sortTimestamp($event);
        if ($latestPost?->date_publication !== null) {
            $sortDate = $latestPost->date_publication->toIso8601String();
        }

        return [
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
