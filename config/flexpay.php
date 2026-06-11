<?php

declare(strict_types=1);

/**
 * Configuration FlexPay — types API et opérateurs Mobile Money (UI interne).
 *
 * Doc : docs/integration-paiement-flexpay/08-MOBILE-MONEY-CORRECTIFS.md
 * Le champ API FlexPay `type` vaut toujours "1" pour Mobile Money ; les codes mpesa/airtel/orange
 * servent uniquement à la validation et à l’interface utilisateur.
 */

$defaultProviders = [
    [
        'type' => 'mpesa',
        'code' => 'mpesa',
        'label' => 'M-Pesa',
        'msisdn_regex' => '^2438[123][0-9]{7}$',
    ],
    [
        'type' => 'airtel',
        'code' => 'airtel',
        'label' => 'Airtel Money',
        'msisdn_regex' => '^2439[0-9]{8}$',
    ],
    [
        'type' => 'orange',
        'code' => 'orange',
        'label' => 'Orange Money',
        'msisdn_regex' => '^2438[459][0-9]{7}$',
    ],
    [
        'type' => 'afri',
        'code' => 'afri',
        'label' => 'Afri Money',
        'msisdn_regex' => '^2439[0-9]{8}$',
    ],
];

$envProviders = env('FLEXPAY_MOBILE_PROVIDERS');
$providers = $defaultProviders;

if (is_string($envProviders) && trim($envProviders) !== '') {
    $decoded = json_decode($envProviders, true);
    if (is_array($decoded) && $decoded !== []) {
        $providers = $decoded;
    }
}

return [
    'flexpay_mobile_money_api_type' => (string) env('FLEXPAY_MOBILE_MONEY_API_TYPE', '1'),
    'flexpay_card_api_type' => (string) env('FLEXPAY_CARD_API_TYPE', '2'),
    'flexpay_mobile_providers' => $providers,
];
