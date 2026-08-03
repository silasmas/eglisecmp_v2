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
 * @property string|null $country
 * @property bool $is_anonymous
 * @property string|null $prayer_team_notification_status
 * @property Carbon|null $prayer_team_notified_at
 * @property string|null $prayer_team_notification_response
 * @property string $message
 * @property string|null $appointment_reason
 * @property int|null $minister_id
 * @property int|null $oriented_from_minister_id
 * @property int|null $bureau_id
 * @property Carbon|null $preferred_at
 * @property string $appointment_status
 * @property string|null $reception_status
 * @property string|null $session_notes
 * @property string|null $session_conclusion
 * @property Carbon|null $received_at
 * @property Carbon|null $completed_at
 * @property string|null $dossier_status
 * @property Carbon|null $closed_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $session_started_at
 * @property int|null $session_duration_minutes
 * @property bool|null $time_respected
 * @property Carbon|null $next_appointment_at
 * @property int|null $reopened_by
 * @property Carbon|null $reopened_at
 * @property string|null $confirmation_sms_status
 * @property Carbon|null $confirmation_sms_sent_at
 * @property string|null $confirmation_sms_response
 */
class SiteInquiry extends Model
{
    public const KIND_PRAYER = 'prayer_request';

    public const KIND_APPOINTMENT = 'appointment';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DECLINED = 'declined';

    public const RECEPTION_AWAITING = 'awaiting';

    public const RECEPTION_IN_PROGRESS = 'in_progress';

    public const RECEPTION_COMPLETED = 'completed';

    public const RECEPTION_ORIENTED = 'oriented';

    public const DOSSIER_OPEN = 'open';

    public const DOSSIER_SUSPENDED = 'suspended';

    public const DOSSIER_CLOSED = 'closed';

    public const DOSSIER_FOLLOW_UP = 'follow_up';

    public const SMS_STATUS_SENT = 'sent';

    public const SMS_STATUS_FAILED = 'failed';

    public const SMS_STATUS_NO_PHONE = 'no_phone';

    public const SMS_STATUS_SIMULATED = 'simulated';

    public const PRAYER_NOTIFY_SENT = 'sent';

    public const PRAYER_NOTIFY_PARTIAL = 'partial';

    public const PRAYER_NOTIFY_FAILED = 'failed';

    public const PRAYER_NOTIFY_NO_RECIPIENT = 'no_recipient';

    public const PRAYER_NOTIFY_PENDING = 'pending';

    protected $fillable = [
        'kind',
        'minister_id',
        'bureau_id',
        'name',
        'email',
        'phone',
        'country',
        'is_anonymous',
        'prayer_team_notification_status',
        'prayer_team_notified_at',
        'prayer_team_notification_response',
        'message',
        'appointment_reason',
        'preferred_at',
        'appointment_status',
        'reception_status',
        'session_notes',
        'session_conclusion',
        'oriented_from_minister_id',
        'received_at',
        'completed_at',
        'dossier_status',
        'closed_at',
        'suspended_at',
        'session_started_at',
        'session_duration_minutes',
        'time_respected',
        'next_appointment_at',
        'reopened_by',
        'reopened_at',
        'confirmation_sms_status',
        'confirmation_sms_sent_at',
        'confirmation_sms_response',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
            'confirmation_sms_sent_at' => 'datetime',
            'prayer_team_notified_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'session_started_at' => 'datetime',
            'next_appointment_at' => 'datetime',
            'reopened_at' => 'datetime',
            'session_duration_minutes' => 'integer',
            'time_respected' => 'boolean',
            'is_anonymous' => 'boolean',
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
     * Pasteur titulaire qui a orienté le fidèle (si applicable).
     *
     * @return BelongsTo<Minister, $this>
     */
    public function orientedFromMinister(): BelongsTo
    {
        return $this->belongsTo(Minister::class, 'oriented_from_minister_id');
    }

    /**
     * Libellés des statuts de réception pastorale.
     *
     * @return array<string, string>
     */
    public static function receptionStatusOptions(): array
    {
        return [
            self::RECEPTION_AWAITING => 'En attente de réception',
            self::RECEPTION_IN_PROGRESS => 'En cours',
            self::RECEPTION_COMPLETED => 'Terminé',
            self::RECEPTION_ORIENTED => 'Orienté',
        ];
    }

    /**
     * Libellés des statuts de dossier pastoral.
     *
     * @return array<string, string>
     */
    public static function dossierStatusOptions(): array
    {
        return [
            self::DOSSIER_OPEN => 'Ouvert',
            self::DOSSIER_SUSPENDED => 'Suspendu',
            self::DOSSIER_CLOSED => 'Clôturé',
            self::DOSSIER_FOLLOW_UP => 'Prochain RDV',
        ];
    }

    /**
     * Compte utilisateur qui a réouvert le dossier.
     *
     * @return BelongsTo<User, $this>
     */
    public function reopenedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
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
     *
     * Le bouton reste visible tant que le fidèle n’a pas été informé par SMS.
     */
    public function canBeConfirmed(): bool
    {
        if ($this->kind !== self::KIND_APPOINTMENT) {
            return false;
        }

        if ($this->appointment_status === self::STATUS_DECLINED) {
            return false;
        }

        if (! $this->preferred_at instanceof Carbon) {
            return false;
        }

        if ($this->preferred_at->isPast()) {
            return false;
        }

        return ! $this->isFaithfulNotifiedBySms();
    }

    /**
     * Indique si un nouvel envoi SMS de confirmation est possible.
     */
    public function canRetryConfirmationSms(): bool
    {
        return $this->canBeConfirmed()
            && filled($this->phone)
            && in_array($this->confirmation_sms_status, [
                self::SMS_STATUS_FAILED,
                self::SMS_STATUS_NO_PHONE,
            ], true);
    }

    /**
     * Indique si le fidèle a été informé par SMS après confirmation.
     */
    public function isFaithfulNotifiedBySms(): bool
    {
        return in_array($this->confirmation_sms_status, [
            self::SMS_STATUS_SENT,
            self::SMS_STATUS_SIMULATED,
        ], true);
    }

    /**
     * Libellé admin du statut SMS de confirmation.
     */
    public function confirmationSmsLabel(): ?string
    {
        return match ($this->confirmation_sms_status) {
            self::SMS_STATUS_SENT => 'Informé',
            self::SMS_STATUS_SIMULATED => 'Simulé',
            self::SMS_STATUS_NO_PHONE => 'Sans téléphone',
            self::SMS_STATUS_FAILED => 'Échec SMS',
            default => null,
        };
    }

    /**
     * Libellé admin du statut e-mail équipe de prière.
     */
    public function prayerTeamNotificationLabel(): ?string
    {
        return match ($this->prayer_team_notification_status) {
            self::PRAYER_NOTIFY_SENT => 'Équipe informée',
            self::PRAYER_NOTIFY_PARTIAL => 'Partiel',
            self::PRAYER_NOTIFY_FAILED => 'Échec e-mail',
            self::PRAYER_NOTIFY_NO_RECIPIENT => 'Sans destinataire',
            self::PRAYER_NOTIFY_PENDING => 'En attente',
            default => null,
        };
    }

    /**
     * Couleur Filament du badge notification équipe de prière.
     */
    public function prayerTeamNotificationBadgeColor(): string
    {
        return match ($this->prayer_team_notification_status) {
            self::PRAYER_NOTIFY_SENT => 'success',
            self::PRAYER_NOTIFY_PARTIAL => 'warning',
            self::PRAYER_NOTIFY_FAILED, self::PRAYER_NOTIFY_NO_RECIPIENT => 'danger',
            self::PRAYER_NOTIFY_PENDING => 'gray',
            default => 'gray',
        };
    }
}
