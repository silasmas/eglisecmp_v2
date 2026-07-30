<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Cellule de maison CMP (rencontre hebdomadaire de quartier).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $commune
 * @property string|null $day
 * @property string|null $time
 * @property string|null $host
 * @property string|null $description
 * @property string|null $address
 * @property float|null $lat
 * @property float|null $lng
 * @property bool $is_active
 * @property int $sort_order
 */
class ChurchCell extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'commune',
        'day',
        'time',
        'host',
        'description',
        'address',
        'lat',
        'lng',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChurchCell $cell): void {
            if (blank($cell->slug)) {
                $cell->slug = Str::slug($cell->name);
            }
        });
    }
}
