<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Testimony;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament / Shield pour le mur de témoignages.
 */
class TestimonyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Testimony');
    }

    public function view(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('View:Testimony');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Testimony');
    }

    public function update(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('Update:Testimony');
    }

    public function delete(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('Delete:Testimony');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Testimony');
    }

    public function restore(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('Restore:Testimony');
    }

    public function forceDelete(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('ForceDelete:Testimony');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Testimony');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Testimony');
    }

    public function replicate(AuthUser $authUser, Testimony $testimony): bool
    {
        return $authUser->can('Replicate:Testimony');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Testimony');
    }
}
