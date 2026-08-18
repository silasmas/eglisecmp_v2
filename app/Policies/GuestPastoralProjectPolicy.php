<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuestPastoralProject;
use App\Support\GuestFormAnswerScope;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Shield pour les projets d’accueil invités.
 */
class GuestPastoralProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GuestPastoralProject')
            || GuestFormAnswerScope::managedDepartmentIds($authUser instanceof \App\Models\User ? $authUser : null) !== [];
    }

    public function view(AuthUser $authUser, GuestPastoralProject $guestPastoralProject): bool
    {
        return $authUser->can('View:GuestPastoralProject')
            || $this->viewAny($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GuestPastoralProject');
    }

    public function update(AuthUser $authUser, GuestPastoralProject $guestPastoralProject): bool
    {
        return $authUser->can('Update:GuestPastoralProject');
    }

    public function delete(AuthUser $authUser, GuestPastoralProject $guestPastoralProject): bool
    {
        return $authUser->can('Delete:GuestPastoralProject');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GuestPastoralProject');
    }
}
