<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentRecordWatcherServiceProvider;
use YacoubAlhaidari\FilamentTour\FilamentTourServiceProvider;

return [
    AppServiceProvider::class,
    FilamentRecordWatcherServiceProvider::class,
    AdminPanelProvider::class,
    FilamentTourServiceProvider::class,
];
