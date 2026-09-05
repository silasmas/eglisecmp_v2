<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ouvrier assigné au service d’un pasteur invité.
 *
 * @property int $id
 * @property int $guest_pastor_id
 * @property int $church_worker_id
 * @property int|null $department_id
 * @property string|null $display_title
 * @property int $sort_order
 */
class GuestPastorWorker extends Model
{
    protected $table = 'guest_pastor_worker';

    protected $fillable = [
        'guest_pastor_id',
        'church_worker_id',
        'department_id',
        'display_title',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    /**
     * @return BelongsTo<ChurchWorker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(ChurchWorker::class, 'church_worker_id');
    }

    /**
     * @return BelongsTo<ChurchDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(ChurchDepartment::class, 'department_id');
    }
}
