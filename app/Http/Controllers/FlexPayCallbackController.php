<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhooks FlexPay (Mobile Money et carte) — mise à jour asynchrone des transactions.
 */
final class FlexPayCallbackController extends Controller
{
    /**
     * Callback FlexPay après paiement Mobile Money.
     *
     * @param  Request  $request  Payload JSON ou formulaire envoyé par FlexPay.
     */
    public function mobile(Request $request): JsonResponse
    {
        Log::info('[flexpay-callback-mobile]', $request->all());

        $this->applyCallbackPayload($request->all(), 'mobile_money');

        return response()->json(['received' => true]);
    }

    /**
     * Callback FlexPay après paiement carte (notification serveur).
     *
     * @param  Request  $request  Payload JSON ou formulaire envoyé par FlexPay.
     */
    public function card(Request $request): JsonResponse
    {
        Log::info('[flexpay-callback-card]', $request->all());

        $this->applyCallbackPayload($request->all(), 'card');

        return response()->json(['received' => true]);
    }

    /**
     * Aligne l’état local d’une transaction à partir du corps du webhook FlexPay.
     *
     * @param  array<string, mixed>  $payload  Données brutes du callback.
     * @param  string  $channel  Canal attendu (mobile_money ou card).
     */
    private function applyCallbackPayload(array $payload, string $channel): void
    {
        $reference = $this->extractString($payload, ['reference', 'Reference', 'merchantReference']);
        $orderNumber = $this->extractString($payload, ['orderNumber', 'order_number', 'OrderNumber']);

        $transaction = null;

        if ($reference !== null) {
            $transaction = Transaction::query()->where('reference', $reference)->first();
        }

        if ($transaction === null && $orderNumber !== null) {
            $transaction = Transaction::query()
                ->where('order_number', $orderNumber)
                ->orWhere('provider_reference', $orderNumber)
                ->first();
        }

        if ($transaction === null) {
            Log::warning('[flexpay-callback] Transaction introuvable.', [
                'reference' => $reference,
                'orderNumber' => $orderNumber,
            ]);

            return;
        }

        if ($transaction->etat === 'paid') {
            return;
        }

        $statusCode = $this->resolveStatusCode($payload);

        if ($statusCode === 0) {
            $transaction->update([
                'etat' => 'paid',
                'chanel' => $channel,
            ]);

            return;
        }

        if ($statusCode === 1) {
            $transaction->update([
                'etat' => 'cancelled',
                'chanel' => $channel,
            ]);
        }
    }

    /**
     * Extrait une chaîne non vide depuis plusieurs clés possibles du payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function extractString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_scalar($value) && (string) $value !== '') {
                return trim((string) $value);
            }
        }

        $nested = $payload['transaction'] ?? null;
        if (is_array($nested)) {
            return $this->extractString($nested, $keys);
        }

        return null;
    }

    /**
     * Déduit le code statut FlexPay (0 = payé, 1 = annulé) depuis le payload webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveStatusCode(array $payload): ?int
    {
        $candidates = [
            $payload['status'] ?? null,
            is_array($payload['transaction'] ?? null) ? ($payload['transaction']['status'] ?? null) : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        $apiCode = $payload['code'] ?? null;
        if ($apiCode === '0' || $apiCode === 0) {
            return 0;
        }

        return null;
    }
}
