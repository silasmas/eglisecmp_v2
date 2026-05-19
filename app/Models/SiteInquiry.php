<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Soumission depuis les pages publiques « Requête de prière » ou « Prendre rendez-vous ».
 *
 * @property int $id
 * @property string $kind Valeur métier {@see SiteInquiry::KIND_PRAYER}, {@see SiteInquiry::KIND_APPOINTMENT}.
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $message
 * @property int|null $minister_id
 * @property Carbon|null $preferred_at
 * @property string $appointment_status
 */
class SiteInquiry extends Model
{
    public const KIND_PRAYER = 'prayer_request';

    public const KIND_APPOINTMENT = 'appointment';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'kind',
        'minister_id',
        'name',
        'email',
        'phone',
        'message',
        'preferred_at',
        'appointment_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
        ];
    }

    /**
     * Pasteur choisi pour un rendez-vous.
     *
     * @return BelongsTo<Minister, $this>
     */
    public function minister(): BelongsTo
    {
        return $this->belongsTo(Minister::class);
    }
}
