<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pilote SMS
    |--------------------------------------------------------------------------
    |
    | log    : journalise dans storage/logs/laravel.log (developpement).
    | keccel : API Keccel (SMS_URL, SMS_TOKEN, SMS_FROM).
    | http   : POST JSON generique (legacy).
    |
    */

    'driver' => env('SMS_DRIVER', 'log'),

    'from' => trim((string) env('SMS_FROM', 'CMP')),

    'max_length' => (int) env('SMS_MAX_LENGTH', 160),

    'keccel' => [
        'url' => trim((string) env('SMS_URL', 'https://api.keccel.com/sms/v1/message.asp')),
        'token' => trim((string) env('SMS_TOKEN', '')),
        'balance_url' => trim((string) env('BALANCE_URL', 'https://api.keccel.com/sms/balance.asp')),
    ],

    'http' => [
        'url' => env('SMS_HTTP_URL'),
        'token' => env('SMS_HTTP_TOKEN'),
    ],

];
