<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ChurchDepartment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChurchDepartmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChurchDepartment');
    }

    public function view(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('View:ChurchDepartment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChurchDepartment');
    }

    public function update(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('Update:ChurchDepartment');
    }

    public function delete(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('Delete:ChurchDepartment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChurchDepartment');
    }

    public function restore(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('Restore:ChurchDepartment');
    }

    public function forceDelete(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('ForceDelete:ChurchDepartment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChurchDepartment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChurchDepartment');
    }

    public function replicate(AuthUser $authUser, ChurchDepartment $churchDepartment): bool
    {
        return $authUser->can('Replicate:ChurchDepartment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChurchDepartment');
    }

}