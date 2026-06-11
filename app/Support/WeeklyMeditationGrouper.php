<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Construit les groupes Méditations à partir de `weekly_service_day` et `date_publication`.
 */
final class WeeklyMeditationGrouper
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function buildGroups(string $locale, string $fallback, Collection $playlistEvents): array
    {
        $groups = [];

        foreach (YoutubePlaylistMatcher::weeklyServiceDaysOrdered() as $weeklyDay) {
            $label = YoutubePlaylistMatcher::groupLabelForWeeklyDay($weeklyDay);
            if ($label === null) {
                continue;
            }

            $videoCount = self::baseQuery($weeklyDay)->count();
            if ($videoCount === 0) {
                continue;
            }

            $latestPost = self::latestPostForDay($weeklyDay);
            $latestItem = $latestPost instanceof Post
                ? SitePublicSerializer::postToSermonArray($latestPost, $locale, $fallback)
                : null;

            $event = self::resolvePlaylistEvent($playlistEvents, $label, $locale, $fallback, $latestPost);
            $thumb = (string) config('site_public.placeholder_image_url', '');

            if (is_array($latestItem) && is_string($latestItem['thumbnail'] ?? null) && $latestItem['thumbnail'] !== '') {
                $thumb = $latestItem['thumbnail'];
            } elseif ($event instanceof Event) {
                $eventThumb = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallback);
                if ($eventThumb !== '') {
                    $thumb = $eventThumb;
                }
            }

            $description = '';
            if ($event instanceof Event) {
                $description = SitePublicSerializer::text($event->description, $locale, $fallback);
            }

            $group = [
                'eventId' => $event instanceof Event ? (string) $event->id : '',
                'title' => $label,
                'description' => $description,
                'thumbnail' => $thumb,
                'videoCount' => $videoCount,
                'syncedCount' => $videoCount,
                'visibility' => 'Publique',
                'weeklyServiceDay' => $weeklyDay,
                'items' => $latestItem !== null ? [$latestItem] : [],
            ];

            if ($latestItem !== null) {
                $group['latestItem'] = $latestItem;
            }

            if ($event instanceof Event) {
                $group['href'] = '/teachings/playlist/'.rawurlencode((string) $event->id)
                    .'?from=meditations&weeklyDay='.rawurlencode($weeklyDay);
            } elseif ($latestPost instanceof Post) {
                $group['href'] = '/teachings/message/'.rawurlencode((string) $latestPost->getKey());
            }

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @return Builder<Post>
     */
    private static function baseQuery(string $weeklyDay): Builder
    {
        return Post::query()
            ->where('is_active', true)
            ->where('weekly_service_day', strtolower(trim($weeklyDay)));
    }

    private static function latestPostForDay(string $weeklyDay): ?Post
    {
        $query = self::baseQuery($weeklyDay)->with(['minister', 'event']);
        EventPostQuery::orderNewestFirst($query);

        $post = $query->first();

        return $post instanceof Post ? $post : null;
    }

    /**
     * @param  Collection<int, Event>  $playlistEvents
     */
    private static function resolvePlaylistEvent(
        Collection $playlistEvents,
        string $label,
        string $locale,
        string $fallback,
        ?Post $latestPost,
    ): ?Event {
        if ($latestPost?->event_id !== null) {
            $fromPost = $latestPost->relationLoaded('event') ? $latestPost->event : $latestPost->event()->first();
            if ($fromPost instanceof Event) {
                return $fromPost;
            }
        }

        foreach ($playlistEvents as $candidate) {
            $title = SitePublicSerializer::text($candidate->designation, $locale, $fallback);
            if (YoutubePlaylistMatcher::meditationGroupForTitle($title) === $label) {
                return $candidate;
            }
        }

        return null;
    }
}
