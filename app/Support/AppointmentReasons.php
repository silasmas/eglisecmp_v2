<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Motifs classifiés des rendez-vous pastoraux.
 */
final class AppointmentReasons
{
    /**
     * @return array<string, string> clé => libellé
     */
    public static function options(): array
    {
        return [
            'premiere_visite' => 'Première visite',
            'entretien_pastoral' => 'Entretien pastoral',
            'conseil_spirituel' => 'Conseil spirituel',
            'accompagnement' => 'Accompagnement',
            'mariage' => 'Mariage / couple',
            'famille' => 'Famille / enfants',
            'discipline' => 'Discipline / réconciliation',
            'autre' => 'Autre',
        ];
    }

    /**
     * Libellé d'un motif, ou tiret si inconnu.
     */
    public static function label(?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        return self::options()[$key] ?? $key;
    }
}
