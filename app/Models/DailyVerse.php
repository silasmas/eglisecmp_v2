<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Verset du jour : une entrée visible exactement 24 h après `publish_at` (fin = `visible_until`).
 *
 * @property int $id
 * @property Carbon $publish_at
 * @property Carbon $visible_until
 * @property array<string, mixed> $reference
 * @property array<string, mixed> $body
 * @property array<string, mixed>|null $image_url
 * @property bool $is_active
 */
class DailyVerse extends Model
{
    protected $fillable = [
        'publish_at',
        'visible_until',
        'reference',
        'body',
        'image_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'visible_until' => 'datetime',
            'reference' => 'array',
            'body' => 'array',
            'image_url' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DailyVerse $verse): void {
            if ($verse->publish_at !== null) {
                $verse->visible_until = $verse->publish_at->copy()->addHours(24);
            }
        });
    }
}
