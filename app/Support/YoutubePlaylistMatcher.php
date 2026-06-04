<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Associe un titre de playlist YouTube aux groupes « méditations » (cultes hebdomadaires).
 */
final class YoutubePlaylistMatcher
{
    /**
     * @return list<array{label: string, match: list<string>}>
     */
    public static function meditationGroups(): array
    {
        return (array) config('site_public.youtube_meditation_playlist_groups', []);
    }

    /**
     * Retourne le libellé du groupe méditation ou null.
     */
    public static function meditationGroupForTitle(string $title): ?string
    {
        $normalized = self::normalize($title);

        foreach (self::meditationGroups() as $group) {
            $label = (string) ($group['label'] ?? '');
            if ($label === '') {
                continue;
            }

            foreach ((array) ($group['match'] ?? []) as $needle) {
                $needleNorm = self::normalize((string) $needle);
                if ($needleNorm !== '' && str_contains($normalized, $needleNorm)) {
                    return $label;
                }
            }
        }

        return null;
    }

    /**
     * Jour de culte Filament (`weekly_service_day`) selon le groupe.
     */
    public static function weeklyServiceDayForGroup(string $groupLabel): ?string
    {
        $map = [
            'Culte d\'enseignement' => 'mercredi',
            'Culte de jeudi etoko' => 'jeudi',
            'Cultes dominicaux' => 'dimanche',
        ];

        return $map[$groupLabel] ?? null;
    }

    public static function normalize(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return strtolower(preg_replace('/\s+/', ' ', trim($ascii !== false ? $ascii : $value)) ?? '');
    }
}
