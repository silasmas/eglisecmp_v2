<?php

namespace App\Providers\Filament;

use AhmedAbdelrhman\FilamentMediaGallery\FilamentMediaGalleryPlugin;
use App\Models\Gallery;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Devletes\FilamentPinnableNavigation\PinnableNavigationPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JibayMcs\Tabbed\TabbedPlugin;
use MrAdder\FilamentLogger\Resources\ActivityResource;
use NoteBrainsLab\FilamentMenuManager\FilamentMenuManagerPlugin;
use Slimani\MediaManager\MediaManagerPlugin;
use Wezlo\FilamentRecordWatcher\FilamentRecordWatcherPlugin;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                ActivityResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentMenuManagerPlugin::make()
                    ->locations([
                        'primary' => 'Menu principal',
                        'footer' => 'Pied de page',
                    ])
                    ->modelSources([
                        Gallery::class,
                    ])
                    ->navigationGroup('Navigation')
                    ->navigationIcon('heroicon-o-bars-3')
                    ->navigationLabel('Menus'),
                FilamentMediaGalleryPlugin::make(),
                FilamentStudioPlugin::make()
                    ->navigationGroup('Studio'),
                FilamentSearchSpotlightPlugin::make(),
                FilamentRecordWatcherPlugin::make(),
                MediaManagerPlugin::make()
                    ->navigationGroup('Médias')
                    ->navigationLabel('Médiathèque')
                    ->navigationIcon('heroicon-o-photo'),
                FilamentClearCachePlugin::make()
                    ->enabled(app()->environment(['local', 'staging'])),
                PinnableNavigationPlugin::make(),
                TabbedPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
