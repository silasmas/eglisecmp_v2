<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduleProgram;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament / Shield pour les programmes site public.
 */
class ScheduleProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ScheduleProgram');
    }

    public function view(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('View:ScheduleProgram');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ScheduleProgram');
    }

    public function update(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('Update:ScheduleProgram');
    }

    public function delete(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('Delete:ScheduleProgram');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ScheduleProgram');
    }

    public function restore(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('Restore:ScheduleProgram');
    }

    public function forceDelete(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('ForceDelete:ScheduleProgram');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ScheduleProgram');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ScheduleProgram');
    }

    public function replicate(AuthUser $authUser, ScheduleProgram $scheduleProgram): bool
    {
        return $authUser->can('Replicate:ScheduleProgram');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ScheduleProgram');
    }
}
