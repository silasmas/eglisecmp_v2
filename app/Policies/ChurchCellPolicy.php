<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChurchCell;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament Shield pour les cellules.
 */
class ChurchCellPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChurchCell');
    }

    public function view(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('View:ChurchCell');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChurchCell');
    }

    public function update(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('Update:ChurchCell');
    }

    public function delete(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('Delete:ChurchCell');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChurchCell');
    }

    public function restore(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('Restore:ChurchCell');
    }

    public function forceDelete(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('ForceDelete:ChurchCell');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChurchCell');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChurchCell');
    }

    public function replicate(AuthUser $authUser, ChurchCell $churchCell): bool
    {
        return $authUser->can('Replicate:ChurchCell');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChurchCell');
    }
}
