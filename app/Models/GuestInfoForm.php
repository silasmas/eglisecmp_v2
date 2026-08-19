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
