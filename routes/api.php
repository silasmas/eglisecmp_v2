<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Site\PublicAppointmentController;
use App\Http\Controllers\Api\Site\PublicContentReactionController;
use App\Http\Controllers\Api\Site\PublicDailyVerseController;
use App\Http\Controllers\Api\Site\PublicEventController;
use App\Http\Controllers\Api\Site\PublicFeaturedPostController;
use App\Http\Controllers\Api\Site\PublicGalleryController;
use App\Http\Controllers\Api\Site\PublicHeroMetaController;
use App\Http\Controllers\Api\Site\PublicMinisterController;
use App\Http\Controllers\Api\Site\PublicOffrandePaymentController;
use App\Http\Controllers\Api\Site\PublicPostController;
use App\Http\Controllers\Api\Site\PublicScheduleProgramController;
use App\Http\Controllers\Api\Site\PublicSiteInquiryController;
use App\Http\Controllers\Api\Site\PublicSiteStatisticController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — site public (SPA React)
|--------------------------------------------------------------------------
|
| Préfixe global : /api (voir bootstrap/app.php). Ces routes sont en lecture
| seule et destinées au front intégré dans Laravel.
|
*/

Route::prefix('site')->group(function (): void {
    Route::get('events', [PublicEventController::class, 'index']);
    Route::get('events/spotlight', [PublicEventController::class, 'spotlight']);
    Route::get('posts', [PublicPostController::class, 'index']);
    Route::get('posts/{post}', [PublicPostController::class, 'show']);
    Route::get('galleries', [PublicGalleryController::class, 'index']);
    Route::get('programs', [PublicScheduleProgramController::class, 'index']);
    Route::get('verse-of-day', [PublicDailyVerseController::class, 'show']);
    Route::get('featured-posts', [PublicFeaturedPostController::class, 'index']);
    Route::get('statistics', [PublicSiteStatisticController::class, 'index']);
    Route::post('inquiries', [PublicSiteInquiryController::class, 'store'])
        ->middleware('throttle:20,1');
    Route::get('ministers', [PublicMinisterController::class, 'index']);
    Route::get('appointments/ministers', [PublicAppointmentController::class, 'ministers']);
    Route::get('appointments/dates', [PublicAppointmentController::class, 'dates']);
    Route::get('appointments/slots', [PublicAppointmentController::class, 'slots']);
    Route::get('hero-meta', [PublicHeroMetaController::class, 'show']);
    Route::get('reaction-keys', [PublicContentReactionController::class, 'keys']);
    Route::get('reactions', [PublicContentReactionController::class, 'index']);
    Route::post('reactions', [PublicContentReactionController::class, 'store'])
        ->middleware('throttle:120,1');

    Route::get('offrandes', [PublicOffrandePaymentController::class, 'offrandes']);
    Route::post('offrandes/init', [PublicOffrandePaymentController::class, 'initTransaction'])
        ->middleware('throttle:60,1');
    Route::post('offrandes/process', [PublicOffrandePaymentController::class, 'processPayment'])
        ->middleware('throttle:60,1');
    Route::get('offrandes/status', [PublicOffrandePaymentController::class, 'checkStatus'])
        ->middleware('throttle:120,1');
});
