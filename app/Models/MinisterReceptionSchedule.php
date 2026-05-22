<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plage horaire hebdomadaire de réception d’un pasteur (jour ISO + heures).
 *
 * @property int $id
 * @property int $minister_id
 * @property int|null $bureau_id
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
        'bureau_id',
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
            'bureau_id' => 'integer',
            'day_of_week' => 'integer',
            'slot_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Plages actives avec bureau renseigné (visibles sur la prise de RDV public).
     *
     * @param  Builder<MinisterReceptionSchedule>  $query
     * @return Builder<MinisterReceptionSchedule>
     */
    public function scopePubliclyBookable($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('bureau_id');
    }

    /**
     * Indique si cette plage peut être proposée aux fidèles en ligne.
     */
    public function isPubliclyBookable(): bool
    {
        return $this->is_active && $this->bureau_id !== null;
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

    /**
     * Bureau de réception associé.
     *
     * @return BelongsTo<Bureau, $this>
     */
    public function bureau(): BelongsTo
    {
        return $this->belongsTo(Bureau::class);
    }
}
