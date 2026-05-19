<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Soumission depuis les pages publiques « Requête de prière » ou « Prendre rendez-vous ».
 *
 * @property int $id
 * @property string $kind Valeur métier {@see SiteInquiry::KIND_PRAYER}, {@see SiteInquiry::KIND_APPOINTMENT}.
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $message
 * @property Carbon|null $preferred_at
 */
class SiteInquiry extends Model
{
    public const KIND_PRAYER = 'prayer_request';

    public const KIND_APPOINTMENT = 'appointment';

    protected $fillable = [
        'kind',
        'name',
        'email',
        'phone',
        'message',
        'preferred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
        ];
    }
}
