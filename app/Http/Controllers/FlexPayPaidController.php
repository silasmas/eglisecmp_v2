<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

/**
 * Gère les retours utilisateur après un paiement carte via FlexPay (success / cancel / decline).
 */
final class FlexPayPaidController extends Controller
{
    /**
     * Redirige vers la SPA `/offrandes` avec indicateurs de résultat.
     *
     * @param  string  $reference  Référence interne (Transaction).
     * @param  string  $amount  Montant (segment d’URL fourni par FlexPay).
     * @param  string  $currency  Code devise (USD, CDF…).
     * @param  string  $status  Statut carte : success, cancel ou decline.
     */
    public function __invoke(string $reference, string $amount, string $currency, string $status): RedirectResponse
    {
        $transaction = Transaction::query()->where('reference', $reference)->first();

        if ($transaction === null) {
            return redirect('/offrandes?erreur=transaction');
        }

        match ($status) {
            'success' => $transaction->update([
                'etat' => 'paid',
                'chanel' => 'card',
            ]),
            'cancel', 'decline' => $transaction->update([
                'etat' => 'cancelled',
                'chanel' => 'card',
            ]),
            default => null,
        };

        return redirect()->to("/offrandes?carte={$status}&ref=".rawurlencode($reference));
    }
}
