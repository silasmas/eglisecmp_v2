<?php

namespace App\Providers;

use App\Listeners\RecordUserLogin;
use App\Models\Post;
use App\Models\User;
use App\Observers\PostObserver;
use App\Observers\UserObserver;
use App\Support\ViteHotFallback;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ViteHotFallback::ensureUsableAssets();
        $this->registerFilamentTourFrenchTranslations();

        Post::observe(PostObserver::class);
        User::observe(UserObserver::class);

        Event::listen(Login::class, [RecordUserLogin::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordUserLogin::class, 'handleFailed']);
    }

    /**
     * Force le chargement des libellés FR du guide Filament Tour
     * (le package ne fournit que en/ar/id ; fallback_locale est fr).
     */
    private function registerFilamentTourFrenchTranslations(): void
    {
        $path = lang_path('vendor/filament-tour/fr/filament-tour.php');
        if (! is_file($path)) {
            return;
        }

        /** @var array<string, mixed> $lines */
        $lines = require $path;
        $prefixed = [];
        foreach (Arr::dot($lines) as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $prefixed['filament-tour.'.$key] = $value;
        }

        Lang::addLines($prefixed, 'fr', 'filament-tour');
    }
}
