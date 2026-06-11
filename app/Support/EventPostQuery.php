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
     * Ordonne les messages du plus récent au plus ancien (publication YouTube puis synchro).
     *
     * @param  Builder<Post>  $query
     */
    public static function orderNewestFirst(Builder $query): void
    {
        $query
            ->orderByDesc('date_publication')
            ->orderByDesc('youtube_synced_at')
            ->orderByDesc('id');
    }

    /**
     * Requête de base : messages actifs liés à l'événement.
     *
     * @return Builder<Post>
     */
    public static function baseQueryForEvent(Event $event): Builder
    {
        return Post::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($event): void {
                self::applyForEvent($sub, $event);
            });
    }

    /**
     * Retourne les messages triés du plus récent au plus ancien (tri SQL).
     *
     * @return list<Post>
     */
    public static function newestPostsForEvent(Event $event): array
    {
        $query = self::baseQueryForEvent($event)->with(['minister', 'event']);
        self::orderNewestFirst($query);

        return $query->get()->all();
    }

    /**
     * Compte les messages actifs rattachés à l'événement.
     */
    public static function activeCountForEvent(Event $event): int
    {
        return self::baseQueryForEvent($event)->count();
    }

    /**
     * Message le plus récent lié à l'événement (une seule requête SQL).
     */
    public static function latestPostForEvent(Event $event): ?Post
    {
        $query = self::baseQueryForEvent($event)->with(['minister', 'event']);
        self::orderNewestFirst($query);

        $post = $query->first();

        return $post instanceof Post ? $post : null;
    }

    /**
     * Trie une collection de posts du plus récent au plus ancien.
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
