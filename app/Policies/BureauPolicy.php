<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bureau;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament / Shield pour les bureaux de réception.
 */
class BureauPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bureau');
    }

    public function view(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('View:Bureau');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bureau');
    }

    public function update(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('Update:Bureau');
    }

    public function delete(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('Delete:Bureau');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Bureau');
    }

    public function restore(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('Restore:Bureau');
    }

    public function forceDelete(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('ForceDelete:Bureau');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bureau');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bureau');
    }

    public function replicate(AuthUser $authUser, Bureau $bureau): bool
    {
        return $authUser->can('Replicate:Bureau');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bureau');
    }
}
