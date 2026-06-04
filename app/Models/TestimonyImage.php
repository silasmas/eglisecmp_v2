<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Image associée à un témoignage du mur public.
 *
 * @property int $id
 * @property int $testimony_id
 * @property string $image
 */
class TestimonyImage extends Model
{
    protected $fillable = [
        'testimony_id',
        'image',
    ];

    /**
     * Témoignage parent.
     *
     * @return BelongsTo<Testimony, $this>
     */
    public function testimony(): BelongsTo
    {
        return $this->belongsTo(Testimony::class);
    }
}
