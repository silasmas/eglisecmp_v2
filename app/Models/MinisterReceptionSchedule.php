<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plage horaire hebdomadaire de réception d’un pasteur (jour ISO + heures).
 *
 * @property int $id
 * @property int $minister_id
 * @property int $day_of_week 1 (lundi) à 7 (dimanche), format ISO-8601
 * @property string $starts_at
 * @property string $ends_at
 * @property int $slot_minutes
 * @property bool $is_active
 */
class MinisterReceptionSchedule extends Model
{
    protected $fillable = [
        'minister_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'slot_minutes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Pasteur associé à cette plage.
     *
     * @return BelongsTo<Minister, $this>
     */
    public function minister(): BelongsTo
    {
        return $this->belongsTo(Minister::class);
    }
}
