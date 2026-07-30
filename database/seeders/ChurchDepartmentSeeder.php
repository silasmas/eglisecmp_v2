<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChurchDepartment;
use Illuminate\Database\Seeder;

/**
 * Départements ministériels CMP (données locales / inscription ouvriers).
 *
 * Idempotent : updateOrCreate sur le slug.
 */
class ChurchDepartmentSeeder extends Seeder
{
    /**
     * Insère ou met à jour les départements présents en local.
     *
     * @return void
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Accueil', 'slug' => 'accueil', 'color' => '#2563EB', 'sort_order' => 1],
            ['name' => 'Intercession', 'slug' => 'intercession', 'color' => '#7C3AED', 'sort_order' => 2],
            ['name' => 'Sécurité', 'slug' => 'securite', 'color' => '#DC2626', 'sort_order' => 3],
            ['name' => 'Restauration', 'slug' => 'restauration', 'color' => '#F97316', 'sort_order' => 4],
            ['name' => 'Technique', 'slug' => 'technique', 'color' => '#0F766E', 'sort_order' => 5],
            ['name' => 'Hygiène', 'slug' => 'hygiene', 'color' => '#06B6D4', 'sort_order' => 6],
            ['name' => 'CREA', 'slug' => 'crea', 'color' => '#DB2777', 'sort_order' => 7],
            ['name' => 'Santé', 'slug' => 'sante', 'color' => '#16A34A', 'sort_order' => 8],
            ['name' => 'Protocole', 'slug' => 'protocole', 'color' => '#7b1d3e', 'sort_order' => 9],
            ['name' => 'Chorale', 'slug' => 'chorale', 'color' => '#CA8A04', 'sort_order' => 10],
            ['name' => 'Jeunesse', 'slug' => 'jeunesse', 'color' => '#EAB308', 'sort_order' => 11],
            ['name' => 'Médias', 'slug' => 'medias', 'color' => '#334155', 'sort_order' => 12],
        ];

        foreach ($items as $item) {
            ChurchDepartment::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'color' => $item['color'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}
