<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChurchDepartment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Départements ministériels de base pour l'inscription ouvriers.
 */
class ChurchDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Accueil', 'color' => '#2563EB'],
            ['name' => 'Intercession', 'color' => '#7C3AED'],
            ['name' => 'Sécurité', 'color' => '#DC2626'],
            ['name' => 'Restauration', 'color' => '#F97316'],
            ['name' => 'Technique', 'color' => '#0F766E'],
            ['name' => 'Hygiène', 'color' => '#06B6D4'],
            ['name' => 'CREA', 'color' => '#DB2777'],
            ['name' => 'Santé', 'color' => '#16A34A'],
            ['name' => 'Protocole', 'color' => '#7b1d3e'],
            ['name' => 'Chorale', 'color' => '#CA8A04'],
            ['name' => 'Jeunesse', 'color' => '#EAB308'],
            ['name' => 'Médias', 'color' => '#334155'],
        ];

        foreach ($items as $index => $item) {
            ChurchDepartment::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'color' => $item['color'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
