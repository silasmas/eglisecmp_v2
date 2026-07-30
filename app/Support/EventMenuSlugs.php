<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Slugs du sous-menu Événements (sync admin ↔ navigation site).
 */
final class EventMenuSlugs
{
    /**
     * @return array<string, string> slug => libellé admin
     */
    public static function options(): array
    {
        return [
            'jeudi-dedicace' => 'Jeudi dédicace',
            'mois-ouvrier' => "Mois de l'ouvrier",
            'seminaires' => 'Séminaires',
            'mois-evangelique' => 'Mois évangélique',
            'bunda-21' => 'Bunda 21',
            'aksanti-mungu' => 'Aksanti Mungu',
            'nativite' => 'Culte de nativité',
            'reveillon' => 'Réveillon',
            'jeunesse' => 'Jeunesse',
        ];
    }
}
