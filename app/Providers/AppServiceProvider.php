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
use Illuminate\Support\Facades\Event;
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

        Post::observe(PostObserver::class);
        User::observe(UserObserver::class);

        Event::listen(Login::class, [RecordUserLogin::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordUserLogin::class, 'handleFailed']);
    }
}
