<?php

declare(strict_types=1);

namespace App\Policies;

use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StudioRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudioRecord');
    }

    public function view(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('View:StudioRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudioRecord');
    }

    public function update(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('Update:StudioRecord');
    }

    public function delete(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('Delete:StudioRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudioRecord');
    }

    public function restore(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('Restore:StudioRecord');
    }

    public function forceDelete(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('ForceDelete:StudioRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudioRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudioRecord');
    }

    public function replicate(AuthUser $authUser, StudioRecord $studioRecord): bool
    {
        return $authUser->can('Replicate:StudioRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudioRecord');
    }
}
