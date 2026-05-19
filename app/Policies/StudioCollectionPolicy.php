<?php

declare(strict_types=1);

namespace App\Policies;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StudioCollectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudioCollection');
    }

    public function view(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('View:StudioCollection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudioCollection');
    }

    public function update(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('Update:StudioCollection');
    }

    public function delete(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('Delete:StudioCollection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudioCollection');
    }

    public function restore(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('Restore:StudioCollection');
    }

    public function forceDelete(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('ForceDelete:StudioCollection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudioCollection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudioCollection');
    }

    public function replicate(AuthUser $authUser, StudioCollection $studioCollection): bool
    {
        return $authUser->can('Replicate:StudioCollection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudioCollection');
    }
}
