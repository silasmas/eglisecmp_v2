<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Support\Str;

/**
 * Génère des références publiques uniques pour les transactions d’offrande (FlexPay / statut polling).
 */
final class TransactionReferenceFactory
{
    /**
     * Fabrique une référence aléatoire non présente en base.
     */
    public static function unique(): string
    {
        do {
            $reference = 'CMP-'.Str::upper(Str::random(12));
        } while (Transaction::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
