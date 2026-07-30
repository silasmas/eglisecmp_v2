<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ChildPresentation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChildPresentationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChildPresentation');
    }

    public function view(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('View:ChildPresentation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChildPresentation');
    }

    public function update(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('Update:ChildPresentation');
    }

    public function delete(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('Delete:ChildPresentation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChildPresentation');
    }

    public function restore(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('Restore:ChildPresentation');
    }

    public function forceDelete(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('ForceDelete:ChildPresentation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChildPresentation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChildPresentation');
    }

    public function replicate(AuthUser $authUser, ChildPresentation $childPresentation): bool
    {
        return $authUser->can('Replicate:ChildPresentation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChildPresentation');
    }

}