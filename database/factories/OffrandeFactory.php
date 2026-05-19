<?php

namespace Database\Factories;

use App\Models\Offrande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offrande>
 */
class OffrandeFactory extends Factory
{
    protected $model = Offrande::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->randomElement(['Dime', 'Action de grace', 'Mission', 'Projet special']),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80) ? 1 : 0,
        ];
    }
}
