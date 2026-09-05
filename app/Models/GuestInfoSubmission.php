<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Soumission d’un formulaire de renseignement par un pasteur invité.
 *
 * @property int $id
 * @property int $guest_pastor_id
 * @property int $form_id
 * @property string $access_token
 * @property string|null $portal_token
 * @property Carbon|null $portal_link_sent_at
 * @property array<string, mixed> $payload
 * @property Carbon $submitted_at
 */
class GuestInfoSubmission extends Model
{
    protected $fillable = [
        'guest_pastor_id',
        'form_id',
        'access_token',
        'portal_token',
        'portal_link_sent_at',
        'payload',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'submitted_at' => 'datetime',
            'portal_link_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GuestInfoSubmission $submission): void {
            if (blank($submission->access_token)) {
                $submission->access_token = Str::lower(Str::random(32));
            }
            if (blank($submission->portal_token)) {
                $submission->portal_token = self::generatePortalToken();
            }
            if ($submission->submitted_at === null) {
                $submission->submitted_at = now();
            }
        });
    }

    /**
     * Génère un token unique pour le portail invité post-soumission.
     */
    public static function generatePortalToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (self::query()->where('portal_token', $token)->exists());

        return $token;
    }

    /**
     * Garantit un portal_token et le retourne.
     */
    public function ensurePortalToken(): string
    {
        if (blank($this->portal_token)) {
            $this->portal_token = self::generatePortalToken();
            $this->save();
        }

        return (string) $this->portal_token;
    }

    /**
     * URL du portail réponses (départements).
     */
    public function publicResponsesUrl(): string
    {
        return url('/accueil-invite/reponses/'.$this->access_token);
    }

    /**
     * Lien court du portail réponses (optionnellement filtré par département).
     */
    public function shortResponsesUrl(?int $departmentId = null): string
    {
        $url = url('/r/'.$this->access_token);
        if ($departmentId !== null && $departmentId > 0) {
            $url .= '?dept='.$departmentId;
        }

        return $url;
    }

    /**
     * URL publique du portail invité (tenues, équipe, jours, liturgie).
     */
    public function publicPortalUrl(): string
    {
        return url('/accueil-invite/portail/'.$this->ensurePortalToken());
    }

    /**
     * Lien court du portail invité.
     */
    public function shortPortalUrl(): string
    {
        return url('/p/'.$this->ensurePortalToken());
    }

    /**
     * Pasteur invité.
     *
     * @return BelongsTo<GuestPastor, $this>
     */
    public function guestPastor(): BelongsTo
    {
        return $this->belongsTo(GuestPastor::class, 'guest_pastor_id');
    }

    /**
     * Formulaire.
     *
     * @return BelongsTo<GuestInfoForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(GuestInfoForm::class, 'form_id');
    }

    /**
     * Notifications envoyées aux départements pour cette soumission.
     *
     * @return HasMany<GuestDepartmentNotification, $this>
     */
    public function departmentNotifications(): HasMany
    {
        return $this->hasMany(GuestDepartmentNotification::class, 'guest_info_submission_id');
    }
}
