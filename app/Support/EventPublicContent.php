<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;

/**
 * Détermine le lien public vers le contenu d'un événement (playlist ou message).
 */
final class EventPublicContent
{
    /**
     * @return array{contentHref: string|null, contentType: string|null, contentLabel: string|null, contentCount: int}
     */
    public static function resolve(Event $event): array
    {
        $count = max(
            (int) ($event->youtube_playlist_item_count ?? 0),
            EventPostQuery::activeCountForEvent($event)
        );

        $playlistId = $event->youtube_playlist_id;

        if ($count > 0 && is_string($playlistId) && trim($playlistId) !== '') {
            return [
                'contentHref' => '/teachings/playlist/'.(string) $event->getKey(),
                'contentType' => 'playlist',
                'contentLabel' => $count > 1 ? 'Ouvrir la playlist' : 'Voir le message',
                'contentCount' => $count,
            ];
        }

        if ($count > 0) {
            $post = EventPostQuery::latestPostForEvent($event);
            if ($post !== null) {
                return [
                    'contentHref' => '/teachings/message/'.(string) $post->getKey(),
                    'contentType' => 'message',
                    'contentLabel' => 'Voir le message',
                    'contentCount' => $count,
                ];
            }
        }

        if (is_string($playlistId) && trim($playlistId) !== '') {
            return [
                'contentHref' => '/teachings/playlist/'.(string) $event->getKey(),
                'contentType' => 'playlist',
                'contentLabel' => 'Ouvrir la playlist',
                'contentCount' => 0,
            ];
        }

        return [
            'contentHref' => null,
            'contentType' => null,
            'contentLabel' => null,
            'contentCount' => 0,
        ];
    }
}
