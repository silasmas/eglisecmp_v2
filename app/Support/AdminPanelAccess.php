<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Contrôle d’accès aux menus / pages plugins du panneau admin via permissions Shield.
 */
final class AdminPanelAccess
{
    /**
     * Indique si l’utilisateur peut accéder à une page (permission Shield ou super_admin).
     *
     * @param  string  $permission  Ex. « View:MenuManagerPage ».
     */
    public static function canViewPage(?User $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can($permission);
    }

    /**
     * Accès au groupe Navigation (menus du site).
     */
    public static function canAccessNavigation(?User $user): bool
    {
        return self::canViewPage($user, 'View:MenuManagerPage');
    }

    /**
     * Accès au groupe Médias (médiathèque).
     */
    public static function canAccessMedias(?User $user): bool
    {
        return self::canViewPage($user, 'View:MediaManager');
    }

    /**
     * Accès à « Mes suivis » (Record Watcher).
     */
    public static function canAccessMyWatches(?User $user): bool
    {
        return self::canViewPage($user, 'View:MyWatchesPage');
    }

    /**
     * Accès aux pages Système (hors sync BDD réservée super_admin).
     */
    public static function canAccessSysteme(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('View:SiteSchedulerPage')
            || $user->can('ViewAny:YoutubeSyncRun');
    }
}
