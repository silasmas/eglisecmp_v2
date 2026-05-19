<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SiteStatistic;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Filament / Shield pour les statistiques d’accueil.
 */
class SiteStatisticPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteStatistic');
    }

    public function view(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('View:SiteStatistic');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteStatistic');
    }

    public function update(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('Update:SiteStatistic');
    }

    public function delete(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('Delete:SiteStatistic');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SiteStatistic');
    }

    public function restore(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('Restore:SiteStatistic');
    }

    public function forceDelete(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('ForceDelete:SiteStatistic');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SiteStatistic');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SiteStatistic');
    }

    public function replicate(AuthUser $authUser, SiteStatistic $siteStatistic): bool
    {
        return $authUser->can('Replicate:SiteStatistic');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SiteStatistic');
    }
}
