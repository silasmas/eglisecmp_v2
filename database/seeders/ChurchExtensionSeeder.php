<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChurchExtension;
use Illuminate\Database\Seeder;

/**
 * Importe les extensions CMP initiales (siège + diaspora).
 */
class ChurchExtensionSeeder extends Seeder
{
    /**
     * Remplit church_extensions si la table est vide.
     */
    public function run(): void
    {
        if (ChurchExtension::query()->exists()) {
            return;
        }

        $rows = [
            ['name' => 'CMP Siège', 'city' => 'Kinshasa', 'country' => 'RD Congo', 'address' => '4524, Avenue des Forces Armées, Gombe', 'lat' => -4.30545, 'lng' => 15.28672, 'description' => 'Maison mère du Centre Missionnaire Philadelphie.', 'leader_name' => 'Pasteur Ken Luamba', 'sort_order' => 1],
            ['name' => 'CMP Lubumbashi', 'city' => 'Lubumbashi', 'country' => 'RD Congo', 'address' => 'Lubumbashi, Haut-Katanga', 'lat' => -11.6876, 'lng' => 27.5026, 'description' => 'Extension missionnaire au Katanga.', 'sort_order' => 2],
            ['name' => 'CMP Matadi', 'city' => 'Matadi', 'country' => 'RD Congo', 'address' => 'Matadi, Kongo-Central', 'lat' => -5.816, 'lng' => 13.45, 'description' => 'Présence CMP dans le Kongo-Central.', 'sort_order' => 3],
            ['name' => 'CMP Bruxelles', 'city' => 'Bruxelles', 'country' => 'Belgique', 'address' => 'Bruxelles, Belgique', 'lat' => 50.8503, 'lng' => 4.3517, 'description' => 'Communauté CMP en diaspora européenne.', 'sort_order' => 4],
            ['name' => 'CMP Paris', 'city' => 'Paris', 'country' => 'France', 'address' => 'Paris, France', 'lat' => 48.8566, 'lng' => 2.3522, 'description' => 'Assemblée CMP en France.', 'sort_order' => 5],
            ['name' => 'CMP Johannesburg', 'city' => 'Johannesburg', 'country' => 'Afrique du Sud', 'address' => 'Johannesburg, Afrique du Sud', 'lat' => -26.2041, 'lng' => 28.0473, 'description' => 'Extension CMP en Afrique australe.', 'sort_order' => 6],
            ['name' => 'CMP Montréal', 'city' => 'Montréal', 'country' => 'Canada', 'address' => 'Montréal, Québec', 'lat' => 45.5017, 'lng' => -73.5673, 'description' => 'Communauté CMP en Amérique du Nord.', 'sort_order' => 7],
            ['name' => 'CMP Washington DC', 'city' => 'Washington', 'country' => 'États-Unis', 'address' => 'Washington DC, USA', 'lat' => 38.9072, 'lng' => -77.0369, 'description' => 'Présence CMP aux États-Unis.', 'sort_order' => 8],
            ['name' => 'CMP Londres', 'city' => 'Londres', 'country' => 'Royaume-Uni', 'address' => 'Londres, Royaume-Uni', 'lat' => 51.5074, 'lng' => -0.1278, 'description' => 'Assemblée CMP au Royaume-Uni.', 'sort_order' => 9],
            ['name' => 'CMP Dubaï', 'city' => 'Dubaï', 'country' => 'Émirats arabes unis', 'address' => 'Dubaï, EAU', 'lat' => 25.2048, 'lng' => 55.2708, 'description' => 'Cellule / extension CMP au Moyen-Orient.', 'sort_order' => 10],
        ];

        foreach ($rows as $row) {
            ChurchExtension::query()->create([
                ...$row,
                'is_active' => true,
            ]);
        }
    }
}
