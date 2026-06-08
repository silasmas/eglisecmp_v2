<?php

declare(strict_types=1);

/**
 * Tâches planifiées du site (scheduler Laravel) et cron HTTP de secours.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Tâches enregistrées (routes/console.php)
    |--------------------------------------------------------------------------
    */
    'tasks' => [
        [
            'command' => 'youtube:sync',
            'label' => 'Synchronisation YouTube',
            'frequency' => 'Toutes les 30 minutes',
        ],
        [
            'command' => 'youtube:check-live',
            'label' => 'Détection live YouTube',
            'frequency' => 'Toutes les 3 minutes',
        ],
        [
            'command' => 'events:check-alerts',
            'label' => 'Alertes événements',
            'frequency' => 'Toutes les 5 minutes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Clés cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'last_run' => 'site_scheduler.last_run',
        'http_enabled' => 'site_scheduler.http_enabled',
    ],

];
