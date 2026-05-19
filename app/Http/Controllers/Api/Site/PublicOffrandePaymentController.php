<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Offrande;
use App\Models\Transaction;
use App\Services\FlexPayGatewayService;
use App\Support\TransactionReferenceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API publique pour offrandes : liste, initialisation transaction, lancement paiement FlexPay et statut.
 */
final class PublicOffrandePaymentController extends Controller
{
    /**
     * Types d’offrandes actifs (liste pour la page « Offrandes » SPA).
     */
    public function offrandes(Request $request): JsonResponse
    {
        $rows = Offrande::query()
            ->where('is_active', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'description']);

        return response()->json(['data' => $rows]);
    }

    /**
     * Crée une transaction locale en attente de paiement (mobile ou carte).
     */
    public function initTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offrande_id' => ['required', 'integer', 'exists:offrandes,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'in:CDF,USD'],
            'fullname' => ['nullable', 'string', 'max:250'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $hasActiveOffrande = Offrande::query()
            ->where('id', $validated['offrande_id'])
            ->where('is_active', true)
            ->exists();

        if (! $hasActiveOffrande) {
            return response()->json(['message' => 'Type d\'offrande indisponible.'], 422);
        }

        $transaction = DB::transaction(static function () use ($validated): Transaction {
            return Transaction::query()->create([
                'reference' => TransactionReferenceFactory::unique(),
                'montant' => (float) $validated['montant'],
                'currency' => $validated['currency'],
                'fullname' => $validated['fullname'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'numberPhone' => $validated['phone'] ?? null,
                'description' => $validated['message'] ?? null,
                'offrande_id' => (int) $validated['offrande_id'],
                'type' => 'offrande',
                'chanel' => 'init',
                'etat' => 'init',
            ]);
        });

        return response()->json([
            'data' => [
                'reference' => $transaction->reference,
                'montant' => $transaction->montant,
                'currency' => $transaction->currency,
            ],
        ], 201);
    }

    /**
     * Lance FlexPay (mobile money ou carte) pour une référence déjà initialisée.
     */
    public function processPayment(Request $request, FlexPayGatewayService $flexPay): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
            'channel' => ['required', 'string', 'in:mobile_money,card'],
            'phone' => ['required_if:channel,mobile_money', 'nullable', 'string', 'max:40'],
        ]);

        $transaction = Transaction::query()
            ->where('reference', $validated['reference'])
            ->firstOrFail();

        if (in_array($transaction->etat, ['paid'], true)) {
            return response()->json(['message' => 'Cette offrande est déjà enregistrée comme payée.'], 409);
        }

        if ($validated['channel'] === 'mobile_money') {
            $phone = (string) ($validated['phone'] ?? '');
            $result = $flexPay->initiateMobileMoney($transaction, $phone);

            return response()->json([
                'data' => [
                    'channel' => 'mobile_money',
                    'success' => (bool) ($result['reponse'] ?? false),
                    'message' => (string) ($result['message'] ?? ''),
                    'reference' => $transaction->reference,
                    'orderNumber' => $result['orderNumber'] ?? null,
                ],
            ], ($result['reponse'] ?? false) ? 200 : 422);
        }

        $label = 'Offrande — '.($transaction->fullname ?: 'Bienfaiteur');
        $card = $flexPay->initiateCardPayment($transaction, $label);

        if (! ($card['rep'] ?? false)) {
            return response()->json([
                'message' => (string) ($card['message'] ?? 'Impossible d\'ouvrir le paiement carte.'),
            ], 422);
        }

        $orderNumber = $card['orderNumber'] ?? null;
        $transaction->update([
            'provider_reference' => is_scalar($orderNumber) ? (string) $orderNumber : null,
            'order_number' => is_scalar($orderNumber) ? (string) $orderNumber : null,
            'chanel' => 'card',
            'etat' => 'pending',
        ]);

        return response()->json([
            'data' => [
                'channel' => 'card',
                'success' => true,
                'redirect_url' => (string) ($card['url'] ?? ''),
                'reference' => $transaction->reference,
            ],
        ]);
    }

    /**
     * Lit le statut FlexPay puis aligne la transaction locale pour le polling mobile money.
     */
    public function checkStatus(Request $request, FlexPayGatewayService $flexPay): JsonResponse
    {
        $reference = $request->query('reference');

        if (! is_string($reference) || trim($reference) === '') {
            return response()->json(['message' => 'Paramètre reference requis.'], 422);
        }

        $transaction = Transaction::query()->where('reference', $reference)->first();

        if ($transaction === null) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        if ($transaction->etat === 'paid') {
            return response()->json([
                'data' => ['paid' => true, 'pending' => false, 'reference' => $transaction->reference],
            ]);
        }

        $remote = $flexPay->fetchRemoteStatus($reference);

        if (! ($remote['reponse'] ?? false)) {
            return response()->json([
                'data' => [
                    'paid' => false,
                    'pending' => in_array($transaction->etat, ['init', 'pending'], true),
                    'cancelled' => $transaction->etat === 'cancelled',
                    'reference' => $transaction->reference,
                    'message' => (string) ($remote['message'] ?? 'Statut indisponible.'),
                ],
            ]);
        }

        $code = $remote['status'] ?? null;
        $codeInt = is_numeric($code) ? (int) $code : -1;

        match ($codeInt) {
            0 => $transaction->update(['etat' => 'paid']),
            1 => $transaction->update(['etat' => 'cancelled']),
            default => null,
        };

        $transaction->refresh();

        $paid = $transaction->etat === 'paid';
        $cancelled = $transaction->etat === 'cancelled';

        return response()->json([
            'data' => [
                'paid' => $paid,
                'pending' => ! $paid && ! $cancelled,
                'cancelled' => $cancelled,
                'flexpay_status' => $codeInt,
                'reference' => $transaction->reference,
            ],
        ]);
    }
}
