<?php

namespace Database\Seeders;

use App\Models\Offrande;
use App\Models\Post;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FactoryDataSeeder extends Seeder
{
    public function run(): void
    {
        // Complements de donnees generees pour les environnements de dev/test.
        if (Role::count() === 0) {
            Role::factory()->count(3)->create();
        }

        if (User::count() < 10) {
            User::factory()->count(10 - User::count())->create();
        }

        if (Offrande::count() < 6) {
            Offrande::factory()->count(6 - Offrande::count())->create();
        }

        if (Post::count() < 30) {
            Post::factory()->count(30 - Post::count())->create();
        }

        if (Transaction::count() < 40) {
            $offrandeIds = Offrande::query()->pluck('id')->all();
            if (! empty($offrandeIds)) {
                Transaction::factory()
                    ->count(40 - Transaction::count())
                    ->make()
                    ->each(function (Transaction $transaction) use ($offrandeIds): void {
                        $transaction->offrande_id = fake()->randomElement($offrandeIds);
                        $transaction->save();
                    });
            }
        }

        // Pivot user_roles depuis les donnees disponibles.
        if (! Schema::hasTable('user_roles')) {
            return;
        }

        $roleIds = Role::query()->pluck('id')->all();
        $userIds = User::query()->pluck('id')->all();
        if (empty($roleIds) || empty($userIds)) {
            return;
        }

        foreach ($userIds as $userId) {
            $roleId = fake()->randomElement($roleIds);
            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleId],
                []
            );
        }
    }
}
