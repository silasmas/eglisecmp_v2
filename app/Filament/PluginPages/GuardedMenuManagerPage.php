<?php

declare(strict_types=1);

namespace App\Filament\PluginPages;

use App\Models\User;
use App\Support\AdminPanelAccess;
use NoteBrainsLab\FilamentMenuManager\Pages\MenuManagerPage;

/**
 * Page Menus (Navigation) protégée par la permission Shield View:MenuManagerPage.
 */
class GuardedMenuManagerPage extends MenuManagerPage
{
    /**
     * Autorise l’accès selon les permissions du rôle.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return AdminPanelAccess::canAccessNavigation($user instanceof User ? $user : null);
    }
}
