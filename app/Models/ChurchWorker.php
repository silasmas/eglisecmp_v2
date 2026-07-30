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
 */
class ChurchWorker extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

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
}
