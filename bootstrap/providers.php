<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentRecordWatcherServiceProvider;

return [
    AppServiceProvider::class,
    FilamentRecordWatcherServiceProvider::class,
    AdminPanelProvider::class,
];
