<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\UserRoleSyncService;

/**
 * Observe les comptes utilisateurs pour maintenir Spatie Permission aligné sur `role_id`.
 */
class UserObserver
{
    public function __construct(
        private readonly UserRoleSyncService $roleSync,
    ) {}

    /**
     * @param  User  $user  Compte créé ou mis à jour.
     */
    public function saved(User $user): void
    {
        if ($user->wasRecentlyCreated || $user->wasChanged('role_id')) {
            $this->roleSync->syncSpatieRoleFromLegacy($user);
        }
    }
}
