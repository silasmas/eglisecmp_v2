<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SiteInquiry;
use App\Models\User;
use App\Support\PastoralAccess;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SiteInquiryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteInquiry') || $this->isLinkedPastor($authUser);
    }

    public function view(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('View:SiteInquiry') || $this->canPastoralManage($authUser, $siteInquiry);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteInquiry');
    }

    public function update(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('Update:SiteInquiry') || $this->canPastoralManage($authUser, $siteInquiry);
    }

    public function delete(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('Delete:SiteInquiry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SiteInquiry');
    }

    public function restore(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('Restore:SiteInquiry');
    }

    public function forceDelete(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('ForceDelete:SiteInquiry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SiteInquiry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SiteInquiry');
    }

    public function replicate(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('Replicate:SiteInquiry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SiteInquiry');
    }

    private function isLinkedPastor(AuthUser $authUser): bool
    {
        return $authUser instanceof User && PastoralAccess::linkedMinister($authUser) !== null;
    }

    private function canPastoralManage(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        if (! $authUser instanceof User || $siteInquiry->kind !== SiteInquiry::KIND_APPOINTMENT) {
            return false;
        }

        if (PastoralAccess::canViewAllAppointments($authUser)) {
            return true;
        }

        $minister = PastoralAccess::linkedMinister($authUser);

        return $minister !== null && (int) $siteInquiry->minister_id === (int) $minister->id;
    }
}
