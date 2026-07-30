<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WorshipServiceReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorshipServiceReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WorshipServiceReport');
    }

    public function view(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('View:WorshipServiceReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WorshipServiceReport');
    }

    public function update(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('Update:WorshipServiceReport');
    }

    public function delete(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('Delete:WorshipServiceReport');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WorshipServiceReport');
    }

    public function restore(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('Restore:WorshipServiceReport');
    }

    public function forceDelete(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('ForceDelete:WorshipServiceReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WorshipServiceReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WorshipServiceReport');
    }

    public function replicate(AuthUser $authUser, WorshipServiceReport $worshipServiceReport): bool
    {
        return $authUser->can('Replicate:WorshipServiceReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WorshipServiceReport');
    }

}