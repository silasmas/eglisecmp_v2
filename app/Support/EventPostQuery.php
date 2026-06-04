<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

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
        return Post::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($event): void {
                self::applyForEvent($sub, $event);
            })
            ->orderByDesc('date_publication')
            ->orderByDesc('id')
            ->first();
    }
}
