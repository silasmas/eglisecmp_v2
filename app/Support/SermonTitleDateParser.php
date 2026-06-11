<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Extrait une date de culte depuis le titre d’un message (ex. « Dimanche 24 mai 2026 »).
 */
final class SermonTitleDateParser
{
    /** @var array<string, int> */
    private const MONTHS = [
        'janvier' => 1,
        'fevrier' => 2,
        'février' => 2,
        'mars' => 3,
        'avril' => 4,
        'mai' => 5,
        'juin' => 6,
        'juillet' => 7,
        'aout' => 8,
        'août' => 8,
        'septembre' => 9,
        'octobre' => 10,
        'novembre' => 11,
        'decembre' => 12,
        'décembre' => 12,
    ];

    /**
     * Tente de lire la date du culte dans le titre affiché.
     */
    public static function parse(string $title): ?Carbon
    {
        $normalized = mb_strtolower(trim($title));

        if ($normalized === '') {
            return null;
        }

        $pattern = '/\b(?:lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)?\s*(\d{1,2})\s+'
            .'(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)'
            .'\s+(20\d{2})\b/u';

        if (preg_match($pattern, $normalized, $matches) !== 1) {
            return null;
        }

        $day = (int) $matches[1];
        $monthKey = str_replace('é', 'e', $matches[2]);
        $monthKey = str_replace('û', 'u', $monthKey);
        $month = self::MONTHS[$monthKey] ?? self::MONTHS[$matches[2]] ?? null;
        $year = (int) $matches[3];

        if ($month === null || $day < 1 || $day > 31 || $year < 2000 || $year > 2100) {
            return null;
        }

        try {
            return Carbon::create($year, $month, $day, 12, 0, 0);
        } catch (\Throwable) {
            return null;
        }
    }
}
