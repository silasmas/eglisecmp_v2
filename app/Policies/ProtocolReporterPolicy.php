<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProtocolReporter;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProtocolReporterPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProtocolReporter');
    }

    public function view(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('View:ProtocolReporter');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProtocolReporter');
    }

    public function update(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('Update:ProtocolReporter');
    }

    public function delete(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('Delete:ProtocolReporter');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProtocolReporter');
    }

    public function restore(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('Restore:ProtocolReporter');
    }

    public function forceDelete(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('ForceDelete:ProtocolReporter');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProtocolReporter');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProtocolReporter');
    }

    public function replicate(AuthUser $authUser, ProtocolReporter $protocolReporter): bool
    {
        return $authUser->can('Replicate:ProtocolReporter');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProtocolReporter');
    }

}