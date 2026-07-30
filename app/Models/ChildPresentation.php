<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Demande publique de présentation d'enfant(s) au culte (2e / 4e dimanche).
 *
 * @property int $id
 * @property int $children_count
 * @property string $parent_names
 * @property string $phone
 * @property bool $phone_verified
 * @property string|null $birth_certificate_path
 * @property string|null $parent_id_document_path
 * @property Carbon $presentation_date
 * @property string $status
 * @property string|null $confirmation_sms_status
 * @property Carbon|null $confirmation_sms_sent_at
 * @property string|null $confirmation_sms_response
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 */
class ChildPresentation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DECLINED = 'declined';

    public const SMS_STATUS_SENT = 'sent';

    public const SMS_STATUS_FAILED = 'failed';

    public const SMS_STATUS_NO_PHONE = 'no_phone';

    public const SMS_STATUS_SIMULATED = 'simulated';

    protected $fillable = [
        'children_count',
        'parent_names',
        'phone',
        'phone_verified',
        'birth_certificate_path',
        'parent_id_document_path',
        'presentation_date',
        'status',
        'confirmation_sms_status',
        'confirmation_sms_sent_at',
        'confirmation_sms_response',
        'confirmed_at',
        'confirmed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'children_count' => 'integer',
            'phone_verified' => 'boolean',
            'presentation_date' => 'date',
            'confirmation_sms_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Enfants liés à cette demande de présentation.
     *
     * @return HasMany<PresentedChild, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(PresentedChild::class);
    }

    /**
     * Administrateur ayant validé la présentation.
     *
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Indique si la demande peut encore être confirmée.
     */
    public function canBeConfirmed(): bool
    {
        if ($this->status === self::STATUS_DECLINED) {
            return false;
        }

        if ($this->status === self::STATUS_CONFIRMED && $this->isParentNotifiedBySms()) {
            return false;
        }

        if (! $this->presentation_date instanceof Carbon) {
            return false;
        }

        return ! $this->presentation_date->copy()->startOfDay()->isPast();
    }

    /**
     * SMS de confirmation déjà notifié au parent.
     */
    public function isParentNotifiedBySms(): bool
    {
        return in_array($this->confirmation_sms_status, [
            self::SMS_STATUS_SENT,
            self::SMS_STATUS_SIMULATED,
        ], true);
    }
}
