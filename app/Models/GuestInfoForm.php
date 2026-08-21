<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Formulaire de renseignement configurable pour un projet d’accueil.
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string $slug
 * @property bool $is_published
 * @property string $layout_mode
 * @property Carbon|null $visible_from
 * @property Carbon|null $visible_until
 * @property string|null $access_password
 * @property array<string, mixed>|null $design
 * @property string|null $intro_html
 * @property string|null $cmp_info_html
 */
class GuestInfoForm extends Model
{
    public const LAYOUT_SINGLE = 'single';

    public const LAYOUT_WIZARD = 'wizard';

    protected $fillable = [
        'project_id',
        'title',
        'slug',
        'is_published',
        'layout_mode',
        'visible_from',
        'visible_until',
        'access_password',
        'design',
        'intro_html',
        'cmp_info_html',
    ];

    /**
     * Mot de passe en clair temporaire (non persisté) pour l’affichage admin / mails.
     */
    public ?string $plainAccessPassword = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
            'design' => 'array',
        ];
    }

    /**
     * Indique si le formulaire public s’affiche en assistant multi-étapes.
     */
    public function isWizardLayout(): bool
    {
        return ($this->layout_mode ?? self::LAYOUT_SINGLE) === self::LAYOUT_WIZARD;
    }

    protected static function booted(): void
    {
        static::creating(function (GuestInfoForm $form): void {
            if (blank($form->slug)) {
                $form->slug = Str::slug($form->title).'-'.Str::lower(Str::random(4));
            }
        });
    }

    /**
     * Indique si le formulaire est actuellement ouvert aux invitations.
     */
    public function isCurrentlyVisible(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->visible_from instanceof Carbon && $now->lt($this->visible_from)) {
            return false;
        }

        if ($this->visible_until instanceof Carbon && $now->gt($this->visible_until)) {
            return false;
        }

        return true;
    }

    /**
     * Secondes restantes avant fermeture automatique (null = pas de date de fin).
     */
    public function secondsUntilClose(): ?int
    {
        if (! $this->visible_until instanceof Carbon) {
            return null;
        }

        $seconds = (int) now()->diffInSeconds($this->visible_until, false);

        return max(0, $seconds);
    }

    /**
     * Libellé humain du temps restant avant blocage.
     */
    public function remainingTimeLabel(): ?string
    {
        $seconds = $this->secondsUntilClose();
        if ($seconds === null) {
            return null;
        }

        if ($seconds <= 0) {
            return 'Formulaire bloqué (période terminée)';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($days > 0) {
            return sprintf('%d j %02d h %02d min', $days, $hours, $minutes);
        }

        if ($hours > 0) {
            return sprintf('%d h %02d min %02d s', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return sprintf('%d min %02d s', $minutes, $secs);
        }

        return sprintf('%d s', $secs);
    }

    /**
     * Hash et enregistre le mot de passe d’accès département.
     */
    public function setAccessPasswordPlain(string $plain): void
    {
        $this->access_password = Hash::make($plain);
        $this->plainAccessPassword = $plain;
    }

    /**
     * Vérifie le mot de passe d’accès département.
     */
    public function checkAccessPassword(string $plain): bool
    {
        if ($this->access_password === null || $this->access_password === '') {
            return false;
        }

        return Hash::check($plain, $this->access_password);
    }

    /**
     * Projet lié.
     *
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'project_id');
    }

    /**
     * Rubriques du formulaire.
     *
     * @return HasMany<GuestInfoFormSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(GuestInfoFormSection::class, 'form_id')->orderBy('sort_order');
    }

    /**
     * Soumissions reçues.
     *
     * @return HasMany<GuestInfoSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(GuestInfoSubmission::class, 'form_id');
    }
}
