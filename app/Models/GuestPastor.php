<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pasteur invité (église sœur) rattaché à un projet d’accueil.
 *
 * @property int $id
 * @property int $project_id
 * @property string $full_name
 * @property string|null $church_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $country
 * @property string|null $city
 * @property Carbon|null $arrival_at
 * @property Carbon|null $ministry_at
 * @property string $invite_token
 * @property Carbon|null $form_opened_at
 * @property Carbon|null $form_submitted_at
 */
class GuestPastor extends Model
{
    protected $fillable = [
        'project_id',
        'full_name',
        'church_name',
        'email',
        'phone',
        'country',
        'city',
        'arrival_at',
        'ministry_at',
        'invite_token',
        'form_opened_at',
        'form_submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arrival_at' => 'datetime',
            'ministry_at' => 'datetime',
            'form_opened_at' => 'datetime',
            'form_submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GuestPastor $pastor): void {
            if (blank($pastor->invite_token)) {
                $pastor->invite_token = self::generateInviteToken();
            }
        });
    }

    /**
     * Génère un token court unique pour le lien d’invitation.
     */
    public static function generateInviteToken(): string
    {
        do {
            $token = Str::lower(Str::random(10));
        } while (self::query()->where('invite_token', $token)->exists());

        return $token;
    }

    /**
     * URL publique du formulaire d’invitation.
     */
    public function publicFormUrl(): string
    {
        return url('/accueil-invite/'.$this->invite_token);
    }

    /**
     * Projet d’accueil.
     *
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'project_id');
    }

    /**
     * Soumission du formulaire (si déjà remplie).
     *
     * @return HasOne<GuestInfoSubmission, $this>
     */
    public function submission(): HasOne
    {
        return $this->hasOne(GuestInfoSubmission::class, 'guest_pastor_id');
    }
}
