<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Membre de l'équipe protocole autorisé à saisir les stats de culte.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProtocolReporter extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Recherche un rapporteur actif par numéro normalisé.
     */
    public static function findActiveByPhone(string $normalizedPhone): ?self
    {
        if ($normalizedPhone === '') {
            return null;
        }

        return self::query()
            ->where('is_active', true)
            ->where('phone', $normalizedPhone)
            ->first();
    }
}
