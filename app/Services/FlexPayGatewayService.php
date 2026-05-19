<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Appels HTTP FlexPay (carte bancaire redirection + initiation mobile money + statut).
 */
final class FlexPayGatewayService
{
    /**
     * Demande à FlexPay une session de paiement carte et retourne l’URL à ouvrir côté client.
     *
     * @return array<string, mixed> Clefs : rep (bool), url, orderNumber|null, message
     */
    public function initiateCardPayment(Transaction $transaction, string $description): array
    {
        $token = (string) config('services.flexpay.token', '');
        $merchant = (string) config('services.flexpay.merchant', '');
        $gatewayCard = (string) config('services.flexpay.gateway_card', '');

        if ($token === '' || $merchant === '' || $gatewayCard === '') {
            return ['rep' => false, 'message' => 'Configuration FlexPay carte incomplète (token, marchand ou URL gateway).'];
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');
        $reference = $transaction->reference;
        $amount = $transaction->montant ?? 0;
        $currency = $transaction->currency ?? 'CDF';
        $baseRedirectUrl = "{$appUrl}/paid/{$reference}/{$amount}/{$currency}";
        $callbackUrl = "{$appUrl}/payment/flexpay/callback/card";

        $body = [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => $callbackUrl,
            'approve_url' => "{$baseRedirectUrl}/success",
            'cancel_url' => "{$baseRedirectUrl}/cancel",
            'decline_url' => "{$baseRedirectUrl}/decline",
            'home_url' => "{$appUrl}/",
        ];

        try {
            $response = Http::timeout(35)
                ->asJson()
                ->post($gatewayCard, $body);
        } catch (\Throwable $exception) {
            Log::warning('[flexpay-card] '.$exception->getMessage());

            return ['rep' => false, 'message' => 'Erreur réseau vers FlexPay carte.'];
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $response->json();

        if (is_array($payload) && ($payload['code'] ?? '') === '0') {
            return [
                'rep' => true,
                'url' => (string) ($payload['url'] ?? ''),
                'orderNumber' => $payload['orderNumber'] ?? null,
                'message' => (string) ($payload['message'] ?? ''),
            ];
        }

        return [
            'rep' => false,
            'message' => is_array($payload)
                ? (string) ($payload['message'] ?? 'Réponse FlexPay carte invalide.')
                : 'Réponse FlexPay carte invalide.',
        ];
    }

    /**
     * Déclenche un débit mobile money (confirmation sur le téléphone du fidèle).
     *
     * @return array<string, mixed> Clefs possibles : reponse, message, reference, orderNumber.
     */
    public function initiateMobileMoney(Transaction $transaction, string $phone): array
    {
        $token = (string) config('services.flexpay.token', '');
        $merchant = (string) config('services.flexpay.merchant', '');
        $gatewayMobile = (string) config('services.flexpay.gateway_mobile', '');

        if ($token === '' || $merchant === '' || $gatewayMobile === '') {
            return ['reponse' => false, 'message' => 'Configuration FlexPay mobile incomplète.'];
        }

        $callbackUrl = rtrim((string) config('app.url', ''), '/').'/payment/flexpay/callback/mobile';

        $payload = [
            'merchant' => $merchant,
            'type' => '1',
            'phone' => $phone,
            'reference' => $transaction->reference,
            'amount' => $transaction->montant,
            'currency' => $transaction->currency ?? 'CDF',
            'callbackUrl' => $callbackUrl,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])->timeout(35)->asJson()->post($gatewayMobile, $payload);
        } catch (\Throwable $exception) {
            Log::warning('[flexpay-mobile] '.$exception->getMessage());

            return ['reponse' => false, 'message' => 'Erreur réseau vers FlexPay mobile.'];
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = $response->json();

        if (is_array($decoded) && ($decoded['code'] ?? '') === '0') {
            $orderNumber = $decoded['orderNumber'] ?? null;
            $transaction->update([
                'provider_reference' => $orderNumber,
                'order_number' => is_string($orderNumber) ? $orderNumber : (is_scalar($orderNumber) ? (string) $orderNumber : null),
                'chanel' => 'mobile_money',
                'etat' => 'pending',
            ]);

            return [
                'reponse' => true,
                'message' => 'Paiement en attente de validation sur téléphone.',
                'reference' => $transaction->reference,
                'orderNumber' => $orderNumber,
            ];
        }

        return [
            'reponse' => false,
            'message' => is_array($decoded) ? ($decoded['message'] ?? 'Échec de la transaction mobile.') : 'Échec de la transaction mobile.',
        ];
    }

    /**
     * Interroge FlexPay pour connaître l’état d’une transaction (polling mobile money).
     *
     * @return array<string, mixed> Clefs possibles : reponse, status, message, raw.
     */
    public function fetchRemoteStatus(string $reference): array
    {
        $base = rtrim((string) config('services.flexpay.gateway_check', ''), '/');
        $token = (string) config('services.flexpay.token', '');

        if ($base === '' || $token === '') {
            return ['reponse' => false, 'message' => 'Configuration FlexPay check incomplète.'];
        }

        $url = $base.'/'.rawurlencode($reference);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->timeout(20)->get($url);
        } catch (\Throwable $exception) {
            Log::warning('[flexpay-check] '.$exception->getMessage());

            return ['reponse' => false, 'message' => 'Erreur réseau statut FlexPay.'];
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! is_array($json)) {
            return ['reponse' => false, 'message' => 'Réponse statut FlexPay invalide.'];
        }

        $status = $json['transaction']['status'] ?? null;

        return [
            'reponse' => true,
            'status' => $status,
            'message' => $json['message'] ?? null,
            'raw' => $json,
        ];
    }
}
