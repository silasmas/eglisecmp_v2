<?php

declare(strict_types=1);

namespace App\Policies;

use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StudioDashboardPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudioDashboard');
    }

    public function view(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('View:StudioDashboard');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudioDashboard');
    }

    public function update(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('Update:StudioDashboard');
    }

    public function delete(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('Delete:StudioDashboard');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudioDashboard');
    }

    public function restore(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('Restore:StudioDashboard');
    }

    public function forceDelete(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('ForceDelete:StudioDashboard');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudioDashboard');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudioDashboard');
    }

    public function replicate(AuthUser $authUser, StudioDashboard $studioDashboard): bool
    {
        return $authUser->can('Replicate:StudioDashboard');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudioDashboard');
    }
}
