<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Session liturgique d’un projet (Matin / Midi / Soir / Samedi).
 *
 * @property int $id
 * @property int $project_id
 * @property string $session_key
 * @property string $title
 * @property string|null $starts_at_time
 * @property string|null $ends_at_time
 * @property int $sort_order
 */
class GuestLiturgySession extends Model
{
    protected $fillable = [
        'project_id',
        'session_key',
        'title',
        'starts_at_time',
        'ends_at_time',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'project_id');
    }

    /**
     * @return HasMany<GuestLiturgyItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GuestLiturgyItem::class, 'session_id')->orderBy('sort_order')->orderBy('id');
    }
}
