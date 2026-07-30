<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BundaProgram;
use Illuminate\Auth\Access\HandlesAuthorization;

class BundaProgramPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BundaProgram');
    }

    public function view(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('View:BundaProgram');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BundaProgram');
    }

    public function update(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('Update:BundaProgram');
    }

    public function delete(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('Delete:BundaProgram');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BundaProgram');
    }

    public function restore(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('Restore:BundaProgram');
    }

    public function forceDelete(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('ForceDelete:BundaProgram');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BundaProgram');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BundaProgram');
    }

    public function replicate(AuthUser $authUser, BundaProgram $bundaProgram): bool
    {
        return $authUser->can('Replicate:BundaProgram');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BundaProgram');
    }

}