<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Projet d’accueil de pasteurs invités (lié à un événement CMP).
 *
 * @property int $id
 * @property string $title
 * @property int|null $event_id
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $status
 * @property string|null $notes
 */
class GuestPastoralProject extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'title',
        'event_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_CLOSED => 'Clos',
        ];
    }

    /**
     * Événement CMP associé.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Départements impliqués dans la préparation.
     *
     * @return BelongsToMany<ChurchDepartment, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            ChurchDepartment::class,
            'guest_pastoral_project_department',
            'guest_pastoral_project_id',
            'church_department_id',
        );
    }

    /**
     * Pasteurs invités du projet.
     *
     * @return HasMany<GuestPastor, $this>
     */
    public function guestPastors(): HasMany
    {
        return $this->hasMany(GuestPastor::class, 'project_id');
    }

    /**
     * Formulaire de renseignement du projet.
     *
     * @return HasOne<GuestInfoForm, $this>
     */
    public function form(): HasOne
    {
        return $this->hasOne(GuestInfoForm::class, 'project_id');
    }

    /**
     * Historique des envois d’invitation (tous canaux).
     *
     * @return HasMany<GuestInviteDispatch, $this>
     */
    public function inviteDispatches(): HasMany
    {
        return $this->hasMany(GuestInviteDispatch::class, 'guest_pastoral_project_id');
    }

    /**
     * Tenues prévues pour l’événement.
     *
     * @return HasMany<GuestEventOutfit, $this>
     */
    public function outfits(): HasMany
    {
        return $this->hasMany(GuestEventOutfit::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Sessions liturgiques du projet.
     *
     * @return HasMany<GuestLiturgySession, $this>
     */
    public function liturgySessions(): HasMany
    {
        return $this->hasMany(GuestLiturgySession::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Lettres d’invitation (modèle projet + overrides pasteurs).
     *
     * @return HasMany<GuestInvitationLetter, $this>
     */
    public function invitationLetters(): HasMany
    {
        return $this->hasMany(GuestInvitationLetter::class, 'project_id');
    }

    /**
     * Modèle de lettre du projet (sans pasteur).
     */
    public function projectLetterTemplate(): ?GuestInvitationLetter
    {
        return $this->invitationLetters()->whereNull('guest_pastor_id')->first();
    }

    /**
     * Indique si le portail invité est encore accessible (avant ends_at).
     */
    public function portalIsOpen(): bool
    {
        if ($this->ends_at === null) {
            return true;
        }

        return now()->lte($this->ends_at->copy()->endOfDay());
    }
}
