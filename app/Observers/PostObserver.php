<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Post;
use App\Support\SitePublicSerializer;
use App\Support\YoutubeVideoMetadataFetcher;

/**
 * Mets à jour la durée YouTube après sauvegarde si le lien vidéo a changé ou sur création (API Data v3 facultative).
 */
class PostObserver
{
    /**
     * Appelle l'API YouTube lorsque pertinent pour remplir `youtube_duration_seconds`.
     */
    public function saved(Post $post): void
    {
        $videoId = SitePublicSerializer::youtubeVideoIdFromLink($post->link_url);

        if ($videoId === null) {
            if ($post->youtube_duration_seconds !== null) {
                $post->forceFill(['youtube_duration_seconds' => null])->saveQuietly();
            }

            return;
        }

        if (! $post->wasRecentlyCreated && ! $post->wasChanged('link_url')) {
            return;
        }

        $seconds = YoutubeVideoMetadataFetcher::durationSeconds($videoId);

        if ($seconds === null || $seconds === $post->youtube_duration_seconds) {
            return;
        }

        $post->forceFill(['youtube_duration_seconds' => $seconds])->saveQuietly();
    }
}
