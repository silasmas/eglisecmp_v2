<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserRoleSyncService;
use Illuminate\Database\Migrations\Migration;

/**
 * Recopie les rôles legacy (`role_id`) vers Spatie Permission pour les comptes existants.
 */
return new class extends Migration
{
    public function up(): void
    {
        /** @var UserRoleSyncService $sync */
        $sync = app(UserRoleSyncService::class);

        User::query()
            ->whereNotNull('role_id')
            ->each(function (User $user) use ($sync): void {
                $sync->syncSpatieRoleFromLegacy($user);
            });
    }

    public function down(): void
    {
        //
    }
};
