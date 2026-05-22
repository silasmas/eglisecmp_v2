<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bureau de réception des pasteurs.
 *
 * @property int $id
 * @property string $name
 */
class Bureau extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Plages horaires associées à ce bureau.
     *
     * @return HasMany<MinisterReceptionSchedule, $this>
     */
    public function receptionSchedules(): HasMany
    {
        return $this->hasMany(MinisterReceptionSchedule::class);
    }
}
