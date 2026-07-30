<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Extension CMP (antenne / assemblée) localisée sur la carte mondiale.
 *
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $country
 * @property string|null $address
 * @property string|null $description
 * @property float $lat
 * @property float $lng
 * @property string|null $leader_name
 * @property string|null $leader_photo_path
 * @property bool $is_active
 * @property int $sort_order
 */
class ChurchExtension extends Model
{
    protected $fillable = [
        'name',
        'city',
        'country',
        'address',
        'description',
        'lat',
        'lng',
        'leader_name',
        'leader_photo_path',
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

    /**
     * URL publique de la photo du pasteur / couple pastoral.
     */
    public function leaderPhotoUrl(): ?string
    {
        if ($this->leader_photo_path === null || $this->leader_photo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->leader_photo_path);
    }
}
