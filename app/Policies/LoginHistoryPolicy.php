<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LoginHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoginHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LoginHistory');
    }

    public function view(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('View:LoginHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LoginHistory');
    }

    public function update(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('Update:LoginHistory');
    }

    public function delete(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('Delete:LoginHistory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LoginHistory');
    }

    public function restore(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('Restore:LoginHistory');
    }

    public function forceDelete(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('ForceDelete:LoginHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LoginHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LoginHistory');
    }

    public function replicate(AuthUser $authUser, LoginHistory $loginHistory): bool
    {
        return $authUser->can('Replicate:LoginHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LoginHistory');
    }

}