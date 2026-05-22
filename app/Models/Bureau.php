<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bureau de réception des pasteurs.
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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

    /**
     * Rendez-vous publics associés à ce bureau.
     *
     * @return HasMany<SiteInquiry, $this>
     */
    public function siteInquiries(): HasMany
    {
        return $this->hasMany(SiteInquiry::class);
    }
}
