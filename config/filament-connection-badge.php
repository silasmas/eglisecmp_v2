<?php

declare(strict_types=1);

return [

    'enabled' => env('FILAMENT_CONNECTION_BADGE_ENABLED', true),

    'render_hook' => env(
        'FILAMENT_CONNECTION_BADGE_RENDER_HOOK',
        'panels::user-menu.before'
    ),

    'permission' => env('FILAMENT_CONNECTION_BADGE_PERMISSION'),

    'show_label' => env('FILAMENT_CONNECTION_BADGE_SHOW_LABEL', true),

    /*
    | Overlay plein écran « connexion perdue » — désactivé par défaut car il
    | peut s’afficher à tort lors des changements de page (Livewire / SPA).
    */
    'show_overlay' => env('FILAMENT_CONNECTION_BADGE_SHOW_OVERLAY', false),

    'route' => [
        'prefix' => '_filament-connection-badge',
        'middleware' => ['web'],
        'throttle' => env('FILAMENT_CONNECTION_BADGE_THROTTLE'),
    ],

    'ping_url' => env('FILAMENT_CONNECTION_BADGE_PING_URL'),

    'ping_interval' => (int) env('FILAMENT_CONNECTION_BADGE_PING_INTERVAL', 5000),

    'thresholds' => [
        'full' => (int) env('FILAMENT_CONNECTION_BADGE_THRESHOLD_FULL', 200),
        'medium' => (int) env('FILAMENT_CONNECTION_BADGE_THRESHOLD_MEDIUM', 600),
    ],

    'max_samples' => (int) env('FILAMENT_CONNECTION_BADGE_MAX_SAMPLES', 30),

];
