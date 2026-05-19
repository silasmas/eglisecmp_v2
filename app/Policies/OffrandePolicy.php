<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Offrande;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OffrandePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Offrande');
    }

    public function view(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('View:Offrande');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Offrande');
    }

    public function update(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('Update:Offrande');
    }

    public function delete(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('Delete:Offrande');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Offrande');
    }

    public function restore(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('Restore:Offrande');
    }

    public function forceDelete(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('ForceDelete:Offrande');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Offrande');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Offrande');
    }

    public function replicate(AuthUser $authUser, Offrande $offrande): bool
    {
        return $authUser->can('Replicate:Offrande');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Offrande');
    }
}
