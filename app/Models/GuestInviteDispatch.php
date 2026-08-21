<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Historique d’un envoi d’invitation (e-mail, SMS ou WhatsApp) à un pasteur invité.
 *
 * @property int $id
 * @property int $guest_pastoral_project_id
 * @property int $guest_pastor_id
 * @property string $channel
 * @property string|null $recipient
 * @property string $status
 * @property string|null $message_preview
 * @property array<string, mixed>|null $meta
 * @property int|null $sent_by_user_id
 * @property Carbon|null $sent_at
 */
class GuestInviteDispatch extends Model
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_LINK_READY = 'link_ready';

    protected $fillable = [
        'guest_pastoral_project_id',
        'guest_pastor_id',
        'channel',
        'recipient',
        'status',
        'message_preview',
        'meta',
        'sent_by_user_id',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function channelOptions(): array
    {
        return [
            self::CHANNEL_EMAIL => 'E-mail',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_FAILED => 'Échec',
            self::STATUS_SKIPPED => 'Ignoré',
            self::STATUS_LINK_READY => 'Lien WhatsApp prêt',
        ];
    }

    /**
     * Projet d’accueil.
     *
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'guest_pastoral_project_id');
    }

    /**
     * Pasteur destinataire.
     *
     * @return BelongsTo<GuestPastor, $this>
     */
    public function guestPastor(): BelongsTo
    {
        return $this->belongsTo(GuestPastor::class, 'guest_pastor_id');
    }

    /**
     * Utilisateur ayant déclenché l’envoi.
     *
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
