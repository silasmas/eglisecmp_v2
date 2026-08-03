<?php

declare(strict_types=1);

namespace App\Filament\PluginPages;

use App\Models\User;
use App\Support\AdminPanelAccess;
use Slimani\MediaManager\Pages\MediaManager;

/**
 * Médiathèque protégée par la permission Shield View:MediaManager.
 */
class GuardedMediaManagerPage extends MediaManager
{
    /**
     * Autorise l’accès selon les permissions du rôle.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return AdminPanelAccess::canAccessMedias($user instanceof User ? $user : null);
    }
}
