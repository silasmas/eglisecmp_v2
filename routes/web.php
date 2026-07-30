<?php

declare(strict_types=1);

use App\Http\Controllers\FlexPayCallbackController;
use App\Http\Controllers\FlexPayPaidController;
use App\Http\Controllers\MigrateController;
use App\Http\Controllers\PublicQrDownloadController;
use App\Http\Controllers\SchedulerHttpController;
use App\Http\Controllers\SeedController;
use App\Http\Controllers\ShieldSyncController;
use App\Http\Controllers\StorageLinkController;
use App\Http\Controllers\WorkerBadgePublicController;
use App\Http\Controllers\WorkerBadgeStudioController;
use App\Http\Controllers\WorkerBadgeStudioWorkersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public — SPA React (resources/js/site)
|--------------------------------------------------------------------------
*/

Route::get('/deploy/storage-link/{token}', StorageLinkController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('deploy.storage-link');

Route::get('/deploy/migrate/{token}', MigrateController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('deploy.migrate');

Route::get('/deploy/seed/{token}', SeedController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('deploy.seed');

Route::get('/deploy/shield-sync/{token}', ShieldSyncController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('deploy.shield-sync');

Route::get('/deploy/scheduler/{token}', SchedulerHttpController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('deploy.scheduler');

/*
|--------------------------------------------------------------------------
| Studio badges ouvriers — session admin obligatoire (nouvel onglet)
|--------------------------------------------------------------------------
*/
Route::get('/admin/worker-badge-studio', WorkerBadgeStudioController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.worker-badge-studio');

Route::get('/admin/worker-badge-studio/workers', WorkerBadgeStudioWorkersController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.worker-badge-studio.workers');

Route::get('/public/admin/worker-badge-studio', WorkerBadgeStudioController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.worker-badge-studio.public');

Route::get('/public/admin/worker-badge-studio/workers', WorkerBadgeStudioWorkersController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.worker-badge-studio.workers.public');

/*
|--------------------------------------------------------------------------
| Badge ouvrier public — page module (hors SPA / navbar / FAB)
|--------------------------------------------------------------------------
*/
Route::get('/ouvriers/badge/{token}', WorkerBadgePublicController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('workers.badge.public');

Route::get('/public/ouvriers/badge/{token}', WorkerBadgePublicController::class)
    ->where('token', '[A-Za-z0-9._-]+')
    ->name('workers.badge.public.prefixed');

Route::match(['get', 'post'], '/payment/flexpay/callback/mobile', [FlexPayCallbackController::class, 'mobile'])
    ->name('flexpay.callback.mobile');

Route::match(['get', 'post'], '/payment/flexpay/callback/card', [FlexPayCallbackController::class, 'card'])
    ->name('flexpay.callback.card');

Route::get('/paid/{reference}/{amount}/{currency}/{status}', FlexPayPaidController::class)
    ->where([
        'reference' => '[A-Za-z0-9._-]+',
        'amount' => '[0-9]+(\.[0-9]{1,4})?',
        'currency' => '[A-Za-z]{3}',
        'status' => 'success|cancel|decline',
    ]);

Route::middleware(['web', 'auth'])
    ->get('/admin/qr-download/{key}', PublicQrDownloadController::class)
    ->where('key', '[A-Za-z0-9._-]+')
    ->name('admin.qr-download');

$spaPathPattern = '^(?!admin(?:/|$)|api(?:/|$)|livewire(?:/|$)|broadcasting(?:/|$)|storage(?:/|$)|sanctum(?:/|$)|_ignition(?:/|$)|horizon(?:/|$)|telescope(?:/|$)|build(?:/|$)|paid(?:/|$)|ouvriers/badge(?:/|$)|worker-badge-studio(?:/|$)).*$';

Route::view('/', 'site')->name('site.spa.home');

Route::view('/{path}', 'site')
    ->where('path', $spaPathPattern)
    ->name('site.spa.page');

/*
|--------------------------------------------------------------------------
| SPA sous préfixe /public (hébergements où l’URL publique inclut ce segment)
|--------------------------------------------------------------------------
*/
Route::view('/public', 'site')->name('site.spa.public.home');

Route::view('/public/{path}', 'site')
    ->where('path', $spaPathPattern)
    ->name('site.spa.public.page');
