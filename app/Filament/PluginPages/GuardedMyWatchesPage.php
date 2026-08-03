<?php

declare(strict_types=1);

namespace App\Filament\PluginPages;

use App\Models\User;
use App\Support\AdminPanelAccess;
use Wezlo\FilamentRecordWatcher\Pages\MyWatchesPage;

/**
 * Page « Mes suivis » protégée par la permission Shield View:MyWatchesPage.
 */
class GuardedMyWatchesPage extends MyWatchesPage
{
    /**
     * Autorise l’accès selon les permissions du rôle.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return AdminPanelAccess::canAccessMyWatches($user instanceof User ? $user : null);
    }
}
