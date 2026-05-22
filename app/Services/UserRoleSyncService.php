<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role as LegacyRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Aligne le rôle legacy (`users.role_id`) avec Spatie Permission (`model_has_roles`).
 */
class UserRoleSyncService
{
    /**
     * Recopie le rôle applicatif de l’utilisateur vers Spatie Permission.
     *
     * @param  User  $user  Compte dont le pivot Spatie doit refléter `role_id`.
     */
    public function syncSpatieRoleFromLegacy(User $user): void
    {
        if ($user->role_id === null) {
            $user->syncRoles([]);

            return;
        }

        $legacyRole = LegacyRole::query()->find($user->role_id);

        if ($legacyRole === null || ! filled($legacyRole->name)) {
            Log::warning('Synchronisation rôle Spatie impossible : rôle legacy introuvable.', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
            ]);

            return;
        }

        $user->syncRoles([$legacyRole->name]);
    }

    /**
     * Resynchronise tous les utilisateurs possédant un `role_id`.
     *
     * @return int Nombre de comptes traités.
     */
    public function syncAllUsersFromLegacyRoles(): int
    {
        $count = 0;

        User::query()
            ->whereNotNull('role_id')
            ->each(function (User $user) use (&$count): void {
                $this->syncSpatieRoleFromLegacy($user);
                $count++;
            });

        return $count;
    }
}
