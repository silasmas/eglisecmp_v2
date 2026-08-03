<?php

declare(strict_types=1);

namespace App\Filament\Plugins;

use App\Filament\PluginPages\GuardedMenuManagerPage;
use Filament\Panel;
use NoteBrainsLab\FilamentMenuManager\FilamentMenuManagerPlugin;

/**
 * Menu Manager enregistrant la page protégée par permissions.
 */
class GuardedMenuManagerPlugin extends FilamentMenuManagerPlugin
{
    /**
     * Enregistre la page Menus gardée (au lieu de la page vendor).
     */
    public function register(Panel $panel): void
    {
        $panel->pages([GuardedMenuManagerPage::class]);
    }
}
