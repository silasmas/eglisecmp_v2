<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SiteInquiry;
use App\Models\User;
use App\Support\PastoralAccess;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Accès au module Réception pastorale (réutilise SiteInquiry + pasteurs liés).
 */
class PastoralReceptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $this->canAccessPastoral($authUser);
    }

    public function view(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $this->canManage($authUser, $siteInquiry);
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $this->canManage($authUser, $siteInquiry);
    }

    public function delete(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    /**
     * Admin, titulaire, ou pasteur lié.
     */
    private function canAccessPastoral(AuthUser $authUser): bool
    {
        if (! $authUser instanceof User) {
            return false;
        }

        if ($authUser->can('ViewAny:SiteInquiry') || $authUser->hasRole('super_admin')) {
            return true;
        }

        return PastoralAccess::linkedMinister($authUser) !== null;
    }

    /**
     * Vérifie le droit sur un dossier précis.
     */
    private function canManage(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        if (! $authUser instanceof User || $siteInquiry->kind !== SiteInquiry::KIND_APPOINTMENT) {
            return false;
        }

        if ($authUser->can('View:SiteInquiry') || $authUser->hasRole('super_admin') || PastoralAccess::canViewAllAppointments($authUser)) {
            return true;
        }

        $minister = PastoralAccess::linkedMinister($authUser);

        return $minister !== null && (int) $siteInquiry->minister_id === (int) $minister->id;
    }
}
