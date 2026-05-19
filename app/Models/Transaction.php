<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'provider_reference',
        'order_number',
        'amount_customer',
        'phone',
        'currency',
        'montant',
        'chanel',
        'description',
        'offrande_id',
        'fullname',
        'numberPhone',
        'pays',
        'type',
        'etat',
    ];

    public function offrande(): BelongsTo
    {
        return $this->belongsTo(Offrande::class);
    }
}
