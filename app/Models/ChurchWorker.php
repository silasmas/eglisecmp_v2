<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Dossier ouvrier (inscription publique + badge).
 *
 * @property int $id
 * @property int $department_id
 * @property int|null $user_id
 * @property string $last_name
 * @property string $first_name
 * @property string $gender
 * @property Carbon $birth_date
 * @property string $phone
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $city
 * @property string $commune
 * @property string $quartier
 * @property string $avenue
 * @property string|null $address_reference
 * @property string|null $studies
 * @property string|null $education_level
 * @property string|null $profession
 * @property string|null $skills
 * @property string|null $department_role
 * @property Carbon|null $department_joined_at
 * @property string|null $photo_path
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string $badge_token
 * @property bool $badge_generated
 * @property Carbon|null $badge_generated_at
 * @property string|null $edit_token
 * @property Carbon|null $edit_token_expires_at
 */
class ChurchWorker extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    /** Durée de validité du lien de modification (jours). */
    public const EDIT_TOKEN_TTL_DAYS = 14;

    protected $fillable = [
        'department_id',
        'user_id',
        'last_name',
        'first_name',
        'gender',
        'birth_date',
        'phone',
        'email',
        'email_verified_at',
        'city',
        'commune',
        'quartier',
        'avenue',
        'address_reference',
        'studies',
        'education_level',
        'profession',
        'skills',
        'department_role',
        'department_joined_at',
        'photo_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'badge_token',
        'badge_generated',
        'badge_generated_at',
        'edit_token',
        'edit_token_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'department_joined_at' => 'date',
            'email_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'badge_generated_at' => 'datetime',
            'badge_generated' => 'boolean',
            'edit_token_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChurchWorker $worker): void {
            if (blank($worker->badge_token)) {
                $worker->badge_token = (string) Str::uuid();
            }
            if (blank($worker->status)) {
                $worker->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Validé',
            self::STATUS_REJECTED => 'Refusé',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function genderOptions(): array
    {
        return [
            self::GENDER_MALE => 'Homme',
            self::GENDER_FEMALE => 'Femme',
        ];
    }

    /**
     * Niveaux d’étude proposés dans les formulaires.
     *
     * @return list<string>
     */
    public static function educationLevelOptions(): array
    {
        return [
            'Primaire',
            'Secondaire',
            'Graduat',
            'Licence',
            'Master',
            'Doctorat',
            'Autre',
        ];
    }

    /**
     * Nom complet affiché.
     */
    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * @return BelongsTo<ChurchDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(ChurchDepartment::class, 'department_id');
    }

    /**
     * Compte utilisateur créé après validation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Validateur admin / responsable.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Indique si le badge public est consultable comme « validé ».
     */
    public function hasValidatedBadge(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->badge_generated;
    }

    /**
     * Génère (ou régénère) un jeton de modification public.
     *
     * @param  int|null  $ttlDays  Durée de validité en jours.
     * @return $this
     */
    public function issueEditToken(?int $ttlDays = null): self
    {
        $this->edit_token = (string) Str::uuid();
        $this->edit_token_expires_at = now()->addDays($ttlDays ?? self::EDIT_TOKEN_TTL_DAYS);
        $this->save();

        return $this;
    }

    /**
     * Le jeton de modification est encore utilisable.
     */
    public function hasValidEditToken(): bool
    {
        return filled($this->edit_token)
            && $this->edit_token_expires_at instanceof Carbon
            && $this->edit_token_expires_at->isFuture();
    }

    /**
     * URL publique du formulaire de modification prérempli.
     */
    public function profileEditUrl(): ?string
    {
        if (! filled($this->edit_token)) {
            return null;
        }

        $path = '/ouvriers/modifier/'.$this->edit_token;
        $request = request();

        if ($request !== null && ($request->is('public') || $request->is('public/*'))) {
            return url('/public'.$path);
        }

        $appPath = parse_url((string) config('app.url'), PHP_URL_PATH);
        if (is_string($appPath) && str_ends_with(rtrim($appPath, '/'), '/public')) {
            return rtrim((string) config('app.url'), '/').$path;
        }

        return url($path);
    }
}
