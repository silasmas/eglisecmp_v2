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
 * @property int|null $bureau_id
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
        'bureau_id',
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

    /**
     * Bureau de réception pour le créneau confirmé.
     *
     * @return BelongsTo<Bureau, $this>
     */
    public function bureau(): BelongsTo
    {
        return $this->belongsTo(Bureau::class);
    }

    /**
     * Indique si le rendez-vous peut encore être confirmé par l’admin.
     */
    public function canBeConfirmed(): bool
    {
        if ($this->kind !== self::KIND_APPOINTMENT) {
            return false;
        }

        if ($this->appointment_status !== self::STATUS_PENDING) {
            return false;
        }

        if (! $this->preferred_at instanceof Carbon) {
            return false;
        }

        return $this->preferred_at->isFuture();
    }
}
