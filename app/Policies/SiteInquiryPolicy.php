<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SiteInquiry;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament / Shield pour les formulaires prière et rendez-vous.
 */
class SiteInquiryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteInquiry');
    }

    public function view(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('View:SiteInquiry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteInquiry');
    }

    public function update(AuthUser $authUser, SiteInquiry $siteInquiry): bool
    {
        return $authUser->can('Update:SiteInquiry');
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
}
