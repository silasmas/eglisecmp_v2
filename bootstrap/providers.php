<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentRecordWatcherServiceProvider;
use YacoubAlhaidari\FilamentTour\FilamentTourServiceProvider;

return [
    AppServiceProvider::class,
    FilamentRecordWatcherServiceProvider::class,
    // Avant AdminPanelProvider : les __() du plugin Tour doivent résoudre les traductions FR.
    FilamentTourServiceProvider::class,
    AdminPanelProvider::class,
];
