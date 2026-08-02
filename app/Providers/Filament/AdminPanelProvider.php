<?php

namespace App\Providers\Filament;

use AhmedAbdelrhman\FilamentMediaGallery\FilamentMediaGalleryPlugin;
use App\Filament\Pages\Dashboard;
use App\Models\Gallery;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Devletes\FilamentPinnableNavigation\PinnableNavigationPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
use YacoubAlhaidari\FilamentTour\FilamentTourPlugin;

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
            ->widgets([])
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
                FilamentShieldPlugin::make()
                    ->navigationGroup('Administration')
                    ->navigationLabel('Rôles & permissions')
                    ->navigationIcon('heroicon-o-shield-check'),
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
                FilamentTourPlugin::make()
                    ->showTourButton(true)
                    ->tourButtonIcon('heroicon-o-academic-cap')
                    ->tourButtonColor('info')
                    ->tourButtonTooltip('Visite guidée du panneau')
                    ->welcomeStep([
                        'id' => 'welcome',
                        'title' => 'Bienvenue dans l’administration CMP',
                        'text' => '<strong>Découvrez les menus principaux selon vos accès.</strong><br><br>Cette visite guide les sections utiles pour votre rôle.',
                        'buttons' => [
                            ['text' => 'Passer', 'action' => 'cancel', 'secondary' => true],
                            ['text' => 'Commencer', 'action' => 'next', 'secondary' => false],
                        ],
                    ])
                    ->finishStep([
                        'id' => 'finish',
                        'title' => 'Visite terminée',
                        'text' => '<strong>Vous êtes prêt à utiliser le panneau.</strong><br><br>Relancez la visite via l’icône 🎓 du menu utilisateur.',
                        'buttons' => [
                            ['text' => 'Retour', 'action' => 'back', 'secondary' => true],
                            ['text' => 'Terminer', 'action' => 'complete', 'secondary' => false],
                        ],
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
