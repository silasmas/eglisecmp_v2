<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuestInfoSubmission;
use App\Models\User;
use App\Support\GuestFormAnswerScope;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations pour consulter les soumissions (admin = tout, responsable = son département).
 */
class GuestInfoSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        return GuestFormAnswerScope::canViewAll($authUser)
            || GuestFormAnswerScope::managedDepartmentIds($authUser) !== []
            || $authUser->can('ViewAny:GuestInfoSubmission');
    }

    public function view(AuthUser $authUser, GuestInfoSubmission $guestInfoSubmission): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        return GuestFormAnswerScope::canViewSubmission($authUser, $guestInfoSubmission);
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, GuestInfoSubmission $guestInfoSubmission): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, GuestInfoSubmission $guestInfoSubmission): bool
    {
        return $authUser->can('Delete:GuestInfoSubmission');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GuestInfoSubmission');
    }
}
