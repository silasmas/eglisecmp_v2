<?php

declare(strict_types=1);

/**
 * Paramètres métier de la présentation d'enfants (ECODIM, horaires, OTP).
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Âge d'entrée à l'ECODIM (années)
    |--------------------------------------------------------------------------
    |
    | Message affiché à côté de l'âge saisi : temps restant avant l'école
    | du dimanche.
    |
    */
    'ecodim_entry_age_years' => (int) env('CHILD_PRESENTATION_ECODIM_AGE', 3),

    /*
    |--------------------------------------------------------------------------
    | Nombre de dimanches futurs proposés
    |--------------------------------------------------------------------------
    */
    'available_dates_count' => (int) env('CHILD_PRESENTATION_DATES_COUNT', 8),

    /*
    |--------------------------------------------------------------------------
    | OTP SMS
    |--------------------------------------------------------------------------
    */
    'otp_length' => 6,
    'otp_ttl_minutes' => (int) env('CHILD_PRESENTATION_OTP_TTL', 10),
    'otp_max_attempts' => 5,

    /*
    |--------------------------------------------------------------------------
    | Limites d'upload (Mo)
    |--------------------------------------------------------------------------
    */
    'max_document_mb' => (int) env('CHILD_PRESENTATION_MAX_DOC_MB', 5),
];
