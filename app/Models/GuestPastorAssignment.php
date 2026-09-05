<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Jour / session d’intervention d’un pasteur invité (routing).
 *
 * @property int $id
 * @property int $guest_pastor_id
 * @property Carbon $day_date
 * @property string $session_key
 * @property string $label
 * @property string $color
 * @property string|null $location
 * @property int $sort_order
 */
class GuestPastorAssignment extends Model
{
    protected $fillable = [
        'guest_pastor_id',
        'day_date',
        'session_key',
        'label',
        'color',
        'location',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GuestPastor, $this>
     */
    public function guestPastor(): BelongsTo
    {
        return $this->belongsTo(GuestPastor::class, 'guest_pastor_id');
    }
}
