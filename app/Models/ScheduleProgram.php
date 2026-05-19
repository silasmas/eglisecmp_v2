<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Programme affiché sur le site (culte du jour, hebdomadaire, séminaire, créneau live, etc.).
 *
 * @property int $id
 * @property string $kind
 * @property array<string, mixed>|null $title
 * @property array<string, mixed>|null $description
 * @property array<string, mixed>|null $image_url
 * @property array<string, mixed>|null $banner_image
 * @property string|null $day_label
 * @property int|null $weekday
 * @property string|null $time_label
 * @property int|null $live_hour
 * @property int|null $live_minute
 * @property string|null $link_url
 * @property int|null $event_id
 * @property string $icon_key
 * @property bool $grid_wide
 * @property int $sort_order
 * @property bool $is_active
 */
class ScheduleProgram extends Model
{
    public const KIND_DAILY = 'daily';

    public const KIND_WEEKLY = 'weekly';

    public const KIND_SEMINAR = 'seminar';

    public const KIND_LIVE = 'live';

    public const KIND_SPECIAL = 'special';

    protected $fillable = [
        'kind',
        'title',
        'description',
        'image_url',
        'banner_image',
        'day_label',
        'weekday',
        'time_label',
        'live_hour',
        'live_minute',
        'link_url',
        'event_id',
        'icon_key',
        'grid_wide',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'image_url' => 'array',
            'banner_image' => 'array',
            'weekday' => 'integer',
            'live_hour' => 'integer',
            'live_minute' => 'integer',
            'grid_wide' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Événement lié (séminaire / conférence).
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
