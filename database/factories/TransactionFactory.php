<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5, 500);

        return [
            'reference' => 'TX-'.strtoupper(Str::random(12)),
            'provider_reference' => 'PRV-'.strtoupper(Str::random(10)),
            'order_number' => 'ORD-'.fake()->numerify('######'),
            'amount_customer' => (string) $amount,
            'phone' => fake()->phoneNumber(),
            'currency' => fake()->randomElement(['USD', 'CDF', 'EUR']),
            'montant' => $amount,
            'chanel' => fake()->randomElement(['mobile_money', 'card', 'cash']),
            'description' => fake()->sentence(),
            'offrande_id' => 1,
            'fullname' => fake()->name(),
            'numberPhone' => fake()->phoneNumber(),
            'pays' => fake()->country(),
            'type' => fake()->randomElement(['online', 'guichet']),
            'etat' => fake()->randomElement(['0', 'success', 'pending', 'failed']),
        ];
    }
}
