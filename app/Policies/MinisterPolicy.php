<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Minister;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MinisterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Minister');
    }

    public function view(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('View:Minister');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Minister');
    }

    public function update(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('Update:Minister');
    }

    public function delete(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('Delete:Minister');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Minister');
    }

    public function restore(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('Restore:Minister');
    }

    public function forceDelete(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('ForceDelete:Minister');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Minister');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Minister');
    }

    public function replicate(AuthUser $authUser, Minister $minister): bool
    {
        return $authUser->can('Replicate:Minister');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Minister');
    }
}
