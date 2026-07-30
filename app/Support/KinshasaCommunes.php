<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Communes de Kinshasa pour l'adresse physique des ouvriers.
 */
final class KinshasaCommunes
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'Bandalungwa',
            'Barumbu',
            'Bumbu',
            'Gombe',
            'Kalamu',
            'Kasa-Vubu',
            'Kimbanseke',
            'Kinshasa',
            'Kintambo',
            'Kisenso',
            'Lemba',
            'Limete',
            'Lingwala',
            'Makala',
            'Maluku',
            'Masina',
            'Matete',
            'Mont-Ngafula',
            'Ndjili',
            'Ngaba',
            'Ngaliema',
            'Ngiri-Ngiri',
            'Nsele',
            'Selembao',
        ];
    }
}
