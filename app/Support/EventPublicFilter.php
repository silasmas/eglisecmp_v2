<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Filtres de visibilité des événements sur le site public.
 */
final class EventPublicFilter
{
    /**
     * Événements mis en avant sur l’accueil et la page événements :
     * à venir, en cours, ou marqués « à la une » (fenêtre programmée).
     *
     * @param  array<string, mixed>  $row  Ligne sérialisée par SitePublicSerializer.
     */
    public static function isHighlight(array $row): bool
    {
        $status = (string) ($row['temporalStatus'] ?? '');

        if (in_array($status, ['upcoming', 'ongoing'], true)) {
            return true;
        }

        return ($row['featured'] ?? false) === true;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function onlyHighlight(array $rows): array
    {
        return array_values(array_filter($rows, static fn (array $row): bool => self::isHighlight($row)));
    }
}
