<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Requêtes posts liés à un événement (event_id ou playlist YouTube).
 */
final class EventPostQuery
{
    /**
     * Restreint la requête aux publications actives liées à l'événement.
     *
     * @param  Builder<Post>  $query
     */
    public static function applyForEvent(Builder $query, Event $event): void
    {
        $query->where(function (Builder $sub) use ($event): void {
            $sub->where('event_id', $event->id);
            $playlistId = $event->youtube_playlist_id;
            if (is_string($playlistId) && trim($playlistId) !== '') {
                $sub->orWhere('youtube_playlist_id', trim($playlistId));
            }
        });
    }

    /**
     * Ordonne les messages d’un événement du plus récent au plus ancien.
     *
     * @param  Builder<Post>  $query
     */
    public static function orderNewestFirst(Builder $query): void
    {
        $query->orderByDesc('date_publication')->orderByDesc('id');
    }

    /**
     * Retourne les messages triés du plus récent au plus ancien (date de culte dans le titre prioritaire).
     *
     * @return list<Post>
     */
    public static function newestPostsForEvent(Event $event): array
    {
        $posts = Post::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($event): void {
                self::applyForEvent($sub, $event);
            })
            ->orderByDesc('id')
            ->get()
            ->all();

        usort(
            $posts,
            static function (Post $left, Post $right): int {
                $leftStamp = SitePublicSerializer::postSortTimestamp($left);
                $rightStamp = SitePublicSerializer::postSortTimestamp($right);

                return strcmp($rightStamp, $leftStamp);
            },
        );

        return $posts;
    }

    /**
     * Compte les messages actifs rattachés à l'événement.
     */
    public static function activeCountForEvent(Event $event): int
    {
        return Post::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($event): void {
                self::applyForEvent($sub, $event);
            })
            ->count();
    }

    /**
     * Premier message publié (le plus récent) lié à l'événement.
     */
    public static function latestPostForEvent(Event $event): ?Post
    {
        $posts = self::newestPostsForEvent($event);

        return $posts[0] ?? null;
    }

    /**
     * Trie une collection de posts du plus récent au plus ancien (date de culte dans le titre).
     *
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, Post>
     */
    public static function sortPostsNewestFirst(Collection $posts): Collection
    {
        return $posts->sort(function (Post $left, Post $right): int {
            $leftStamp = SitePublicSerializer::postSortTimestamp($left);
            $rightStamp = SitePublicSerializer::postSortTimestamp($right);

            return strcmp($rightStamp, $leftStamp);
        })->values();
    }
}
