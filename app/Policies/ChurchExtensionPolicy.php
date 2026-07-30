<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ChurchExtension;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChurchExtensionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChurchExtension');
    }

    public function view(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('View:ChurchExtension');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChurchExtension');
    }

    public function update(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('Update:ChurchExtension');
    }

    public function delete(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('Delete:ChurchExtension');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChurchExtension');
    }

    public function restore(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('Restore:ChurchExtension');
    }

    public function forceDelete(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('ForceDelete:ChurchExtension');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChurchExtension');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChurchExtension');
    }

    public function replicate(AuthUser $authUser, ChurchExtension $churchExtension): bool
    {
        return $authUser->can('Replicate:ChurchExtension');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChurchExtension');
    }

}