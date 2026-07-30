<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\YoutubeSyncRun;
use Illuminate\Auth\Access\HandlesAuthorization;

class YoutubeSyncRunPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:YoutubeSyncRun');
    }

    public function view(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('View:YoutubeSyncRun');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:YoutubeSyncRun');
    }

    public function update(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('Update:YoutubeSyncRun');
    }

    public function delete(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('Delete:YoutubeSyncRun');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:YoutubeSyncRun');
    }

    public function restore(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('Restore:YoutubeSyncRun');
    }

    public function forceDelete(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('ForceDelete:YoutubeSyncRun');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:YoutubeSyncRun');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:YoutubeSyncRun');
    }

    public function replicate(AuthUser $authUser, YoutubeSyncRun $youtubeSyncRun): bool
    {
        return $authUser->can('Replicate:YoutubeSyncRun');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:YoutubeSyncRun');
    }

}