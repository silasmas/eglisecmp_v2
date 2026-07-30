<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChurchWorkerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChurchWorker') || $this->managesAnyDepartment($authUser);
    }

    public function view(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('View:ChurchWorker') || $this->managesDepartment($authUser, (int) $churchWorker->department_id);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChurchWorker');
    }

    public function update(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('Update:ChurchWorker') || $this->managesDepartment($authUser, (int) $churchWorker->department_id);
    }

    public function delete(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('Delete:ChurchWorker');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChurchWorker');
    }

    public function restore(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('Restore:ChurchWorker');
    }

    public function forceDelete(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('ForceDelete:ChurchWorker');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChurchWorker');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChurchWorker');
    }

    public function replicate(AuthUser $authUser, ChurchWorker $churchWorker): bool
    {
        return $authUser->can('Replicate:ChurchWorker');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChurchWorker');
    }

    private function managesAnyDepartment(AuthUser $authUser): bool
    {
        return $authUser instanceof User
            && ChurchDepartment::query()->where('manager_user_id', $authUser->id)->exists();
    }

    private function managesDepartment(AuthUser $authUser, int $departmentId): bool
    {
        return $authUser instanceof User
            && ChurchDepartment::query()
                ->where('id', $departmentId)
                ->where('manager_user_id', $authUser->id)
                ->exists();
    }
}
