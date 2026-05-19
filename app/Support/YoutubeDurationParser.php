<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lecture de la chaîne PT15M33S renvoyée par l'API YouTube (contentDetails.duration).
 */
final class YoutubeDurationParser
{
    /**
     * Transforme une durée ISO 8601 « PT » en secondes totales ou null si invalide.
     *
     * @param  string|null  $input  Valeur brute (ex. PT1H5M ou PT42S).
     * @return int|null Secondes, ou null.
     */
    public static function iso8601ToSeconds(?string $input): ?int
    {
        if (! is_string($input) || $input === '') {
            return null;
        }

        if (preg_match('/^PT(?:([\d]+)H)?(?:([\d]+)M)?(?:([\d]+)S)?$/', $input, $m) !== 1) {
            return null;
        }

        $hours = isset($m[1]) ? (int) $m[1] : 0;
        $minutes = isset($m[2]) ? (int) $m[2] : 0;
        $seconds = isset($m[3]) ? (int) $m[3] : 0;

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    /**
     * Formate un nombre de secondes pour l'affichage français (minutes ou heures + minutes).
     *
     * @param  int|null  $totalSeconds  Durée réelle ou null.
     */
    public static function formatFrench(?int $totalSeconds, ?string $fallback = null): string
    {
        if ($totalSeconds === null || $totalSeconds <= 0) {
            return $fallback ?? '—';
        }

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);

        if ($hours > 0) {
            return $hours.' h '.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).' min';
        }

        if ($minutes > 0) {
            return $minutes.' min';
        }

        return $totalSeconds.' s';
    }
}
