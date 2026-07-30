<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChurchCell;
use Illuminate\Database\Seeder;

/**
 * Cellules de maison CMP (données locales Kinshasa).
 *
 * Idempotent : updateOrCreate sur le slug.
 */
class ChurchCellSeeder extends Seeder
{
    /**
     * Insère ou met à jour les cellules de référence.
     *
     * @return void
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'Cellule Gombe',
                'slug' => 'cellule-gombe',
                'commune' => 'Gombe',
                'day' => 'Mardi',
                'time' => '18h00',
                'host' => 'Famille Kabongo',
                'description' => 'Communion, prière et étude biblique au cœur de Gombe.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Cellule Lingwala',
                'slug' => 'cellule-lingwala',
                'commune' => 'Lingwala',
                'day' => 'Mercredi',
                'time' => '18h30',
                'host' => 'Famille Mbayo',
                'description' => 'Temps fraternel pour grandir ensemble dans la Parole.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Cellule Kintambo',
                'slug' => 'cellule-kintambo',
                'commune' => 'Kintambo',
                'day' => 'Jeudi',
                'time' => '18h00',
                'host' => 'Famille Ilunga',
                'description' => 'Partage, intercession et encouragement mutuel.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Cellule Limete',
                'slug' => 'cellule-limete',
                'commune' => 'Limete',
                'day' => 'Vendredi',
                'time' => '18h30',
                'host' => 'Famille Tshilombo',
                'description' => 'Cellule familiale ouverte à tous les voisins du quartier.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Cellule Ngaliema',
                'slug' => 'cellule-ngaliema',
                'commune' => 'Ngaliema',
                'day' => 'Mardi',
                'time' => '18h30',
                'host' => 'Famille Kalonji',
                'description' => 'Un foyer pour prier et marcher ensemble dans la foi.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Cellule Masina',
                'slug' => 'cellule-masina',
                'commune' => 'Masina',
                'day' => 'Mercredi',
                'time' => '17h30',
                'host' => 'Famille Mwamba',
                'description' => 'Rencontre de cellule pour les familles de Masina.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Cellule Lemba',
                'slug' => 'cellule-lemba',
                'commune' => 'Lemba',
                'day' => 'Jeudi',
                'time' => '18h00',
                'host' => 'Famille Ngoie',
                'description' => 'Étude biblique et communion fraternelle à Lemba.',
                'sort_order' => 7,
            ],
        ];

        foreach ($items as $item) {
            ChurchCell::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'commune' => $item['commune'],
                    'day' => $item['day'],
                    'time' => $item['time'],
                    'host' => $item['host'],
                    'description' => $item['description'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}
