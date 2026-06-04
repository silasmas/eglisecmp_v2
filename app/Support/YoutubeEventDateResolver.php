<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Dates d'événement dérivées des métadonnées YouTube (playlist).
 */
final class YoutubeEventDateResolver
{
    /**
     * Calcule date_debut / date_fin à partir du titre et de la publication YouTube.
     *
     * @param  array{title: string, publishedAt: string|null}  $playlist
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolveRange(array $playlist): array
    {
        $title = (string) ($playlist['title'] ?? '');
        $publishedAt = isset($playlist['publishedAt']) && is_string($playlist['publishedAt'])
            ? Carbon::parse($playlist['publishedAt'])
            : now();

        $year = self::extractYearFromTitle($title);
        if ($year !== null) {
            $start = Carbon::create($year, $publishedAt->month, min($publishedAt->day, 28), 9, 0, 0);
            $end = $start->copy()->addMonths(2);

            return [$start, $end];
        }

        $start = $publishedAt->copy()->startOfDay();
        $end = $start->copy()->addMonths(3);

        return [$start, $end];
    }

    /**
     * Extrait une année à 4 chiffres du titre (ex. Bunda21 2025).
     */
    public static function extractYearFromTitle(string $title): ?int
    {
        if (preg_match('/\b(20\d{2})\b/', $title, $matches) !== 1) {
            return null;
        }

        $year = (int) $matches[1];

        return $year >= 2000 && $year <= 2100 ? $year : null;
    }

    /**
     * Date de tri chronologique (plus récent en premier côté API).
     */
    public static function sortTimestamp(Event $event): string
    {
        if ($event->youtube_published_at instanceof Carbon) {
            return $event->youtube_published_at->format('Y-m-d H:i:s');
        }

        if ($event->date_debut instanceof Carbon) {
            return $event->date_debut->format('Y-m-d H:i:s');
        }

        return '1970-01-01 00:00:00';
    }
}
