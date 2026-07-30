<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MinisterReceptionSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class MinisterReceptionSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MinisterReceptionSchedule');
    }

    public function view(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('View:MinisterReceptionSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MinisterReceptionSchedule');
    }

    public function update(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('Update:MinisterReceptionSchedule');
    }

    public function delete(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('Delete:MinisterReceptionSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MinisterReceptionSchedule');
    }

    public function restore(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('Restore:MinisterReceptionSchedule');
    }

    public function forceDelete(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('ForceDelete:MinisterReceptionSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MinisterReceptionSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MinisterReceptionSchedule');
    }

    public function replicate(AuthUser $authUser, MinisterReceptionSchedule $ministerReceptionSchedule): bool
    {
        return $authUser->can('Replicate:MinisterReceptionSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MinisterReceptionSchedule');
    }

}