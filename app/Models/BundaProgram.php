<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Programme / édition Bunda 21 (contenu dédié, distinct des événements génériques).
 *
 * @property int $id
 * @property int $edition_year
 * @property array<string, string>|null $title
 * @property array<string, string>|null $subtitle
 * @property array<string, string>|null $description
 * @property array<string, string>|null $body
 * @property array<string, string>|null $hero_image
 * @property string|null $meal_plan_path
 * @property string $meal_plan_label
 * @property int|null $event_id
 * @property bool $is_upcoming_announcement
 * @property string $upcoming_month_label
 * @property array<string, string>|null $upcoming_description
 * @property bool $is_active
 * @property int $sort_order
 */
class BundaProgram extends Model
{
    protected $fillable = [
        'edition_year',
        'title',
        'subtitle',
        'description',
        'body',
        'hero_image',
        'meal_plan_path',
        'meal_plan_label',
        'event_id',
        'is_upcoming_announcement',
        'upcoming_month_label',
        'upcoming_description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'description' => 'array',
            'body' => 'array',
            'hero_image' => 'array',
            'is_upcoming_announcement' => 'boolean',
            'upcoming_description' => 'array',
            'is_active' => 'boolean',
            'edition_year' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Événement lié (playlist YouTube / affiche).
     *
     * @return BelongsTo<Event, BundaProgram>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
