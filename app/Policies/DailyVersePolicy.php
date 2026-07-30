<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DailyVerse;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyVersePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DailyVerse');
    }

    public function view(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('View:DailyVerse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DailyVerse');
    }

    public function update(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('Update:DailyVerse');
    }

    public function delete(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('Delete:DailyVerse');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DailyVerse');
    }

    public function restore(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('Restore:DailyVerse');
    }

    public function forceDelete(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('ForceDelete:DailyVerse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DailyVerse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DailyVerse');
    }

    public function replicate(AuthUser $authUser, DailyVerse $dailyVerse): bool
    {
        return $authUser->can('Replicate:DailyVerse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DailyVerse');
    }

}