<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Catégorie de programme diffusé sur le site (grille d'accueil et bandeau héros).
 */
enum ScheduleProgramKind: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Seminar = 'seminar';
    case Live = 'live';
    case Special = 'special';

    /**
     * Libellés pour les formulaires Filament.
     *
     * @return array<string, string> Valeur enum => libellé humain.
     */
    public static function labels(): array
    {
        return [
            self::Daily->value => 'Culte / rendez-vous du jour',
            self::Weekly->value => 'Hebdomadaire (récurrent)',
            self::Seminar->value => 'Séminaire ou événement à venir',
            self::Live->value => 'Prochain live (créneau récurrent)',
            self::Special->value => 'Spécial',
        ];
    }
}
