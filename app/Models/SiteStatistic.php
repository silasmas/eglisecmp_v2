<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ligne « En chiffres » affichée sur l’accueil SPA (FIDÈLES, extensions, etc.).
 *
 * Les champs `icon_key`, `label`, `numeric_value`, `suffix` et `sort_order`
 * sont affichés côté public ; `is_active` filtre la liste API.
 *
 * @property int $id
 * @property int $sort_order
 * @property string $icon_key
 * @property string $label
 * @property int $numeric_value
 * @property string|null $suffix
 * @property bool $is_active
 */
class SiteStatistic extends Model
{
    protected $fillable = [
        'sort_order',
        'icon_key',
        'label',
        'numeric_value',
        'suffix',
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
}
