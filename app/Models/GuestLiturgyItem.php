<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de liturgie (horaire + activité).
 *
 * @property int $id
 * @property int $session_id
 * @property string|null $starts_at_time
 * @property string|null $ends_at_time
 * @property int|null $duration_minutes
 * @property string $label
 * @property int $sort_order
 */
class GuestLiturgyItem extends Model
{
    protected $fillable = [
        'session_id',
        'starts_at_time',
        'ends_at_time',
        'duration_minutes',
        'label',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GuestLiturgySession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GuestLiturgySession::class, 'session_id');
    }
}
