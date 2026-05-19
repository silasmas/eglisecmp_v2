<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteStatistic;
use Illuminate\Database\Seeder;

/**
 * Remplit les chiffres d’accueil par défaut si la table est vide.
 */
final class SiteStatisticSeeder extends Seeder
{
    public function run(): void
    {
        if (SiteStatistic::query()->exists()) {
            return;
        }

        SiteStatistic::query()->insert([
            [
                'sort_order' => 0,
                'icon_key' => 'users',
                'label' => 'Fidèles',
                'numeric_value' => 3360,
                'suffix' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sort_order' => 1,
                'icon_key' => 'network',
                'label' => 'Extensions',
                'numeric_value' => 10,
                'suffix' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sort_order' => 2,
                'icon_key' => 'grid',
                'label' => 'Cellules',
                'numeric_value' => 7,
                'suffix' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sort_order' => 3,
                'icon_key' => 'pastors',
                'label' => 'Pastoraux',
                'numeric_value' => 4,
                'suffix' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
