<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Code OTP SMS pour authentifier un numéro (ex. présentation d'enfants).
 *
 * @property int $id
 * @property string $phone
 * @property string $purpose
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property Carbon|null $verified_at
 * @property int $attempts
 */
class PhoneOtp extends Model
{
    public const PURPOSE_CHILD_PRESENTATION = 'child_presentation';

    public const PURPOSE_WORSHIP_REPORT = 'worship_report';

    protected $fillable = [
        'phone',
        'purpose',
        'code_hash',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Indique si le code est encore utilisable.
     */
    public function isValid(): bool
    {
        return $this->verified_at === null
            && $this->expires_at instanceof Carbon
            && $this->expires_at->isFuture();
    }
}
