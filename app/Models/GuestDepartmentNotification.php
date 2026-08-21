<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Historique d’une notification de réponses envoyée à un département (avec accusé de réception).
 *
 * @property int $id
 * @property int $guest_info_submission_id
 * @property int $church_department_id
 * @property string $channel
 * @property string|null $recipient
 * @property string $status
 * @property array<string, mixed>|null $meta
 * @property int|null $sent_by_user_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $acknowledged_at
 * @property string|null $acknowledged_by_name
 * @property string|null $acknowledged_via
 */
class GuestDepartmentNotification extends Model
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const ACK_VIA_PORTAL = 'portal';

    public const ACK_VIA_ADMIN = 'admin';

    protected $fillable = [
        'guest_info_submission_id',
        'church_department_id',
        'channel',
        'recipient',
        'status',
        'meta',
        'sent_by_user_id',
        'sent_at',
        'acknowledged_at',
        'acknowledged_by_name',
        'acknowledged_via',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * Indique si le département a déjà accusé réception.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Soumission concernée.
     *
     * @return BelongsTo<GuestInfoSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(GuestInfoSubmission::class, 'guest_info_submission_id');
    }

    /**
     * Département notifié.
     *
     * @return BelongsTo<ChurchDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(ChurchDepartment::class, 'church_department_id');
    }

    /**
     * Utilisateur ayant déclenché l’envoi (null = automatique à la soumission).
     *
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
