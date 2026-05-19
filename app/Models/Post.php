<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'link_url',
        'image_url',
        'body',
        'author',
        'observation',
        'event_id',
        'slug',
        'is_active',
        'references',
        'date_publication',
        'fichier_url',
        'minister_id',
        'featured_on_home',
        'featured_from',
        'featured_until',
        'featured_sort_order',
        'weekly_service_day',
        'youtube_duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'image_url' => 'array',
            'body' => 'array',
            'observation' => 'array',
            'references' => 'array',
            'fichier_url' => 'array',
            'is_active' => 'boolean',
            'date_publication' => 'datetime',
            'featured_on_home' => 'boolean',
            'featured_from' => 'datetime',
            'featured_until' => 'datetime',
            'featured_sort_order' => 'integer',
            'youtube_duration_seconds' => 'integer',
        ];
    }

    public function getLocalizedValue(string $attribute, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $value = $this->getAttribute($attribute);

        if (is_array($value)) {
            return $value[$locale]
                ?? $value[$fallback]
                ?? collect($value)->first(fn ($item): bool => filled($item))
                ?? null;
        }

        if (! is_string($value) || blank($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return $value;
        }

        return $decoded[$locale]
            ?? $decoded[$fallback]
            ?? collect($decoded)->first(fn ($item): bool => filled($item))
            ?? null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function minister(): BelongsTo
    {
        return $this->belongsTo(Minister::class);
    }

    public function getSpeakerName(): string
    {
        if (filled($this->minister?->fullname)) {
            return (string) $this->minister->fullname;
        }

        return (string) ($this->author ?: 'Inconnu');
    }

    public function getSpeakerInitials(): string
    {
        $source = trim($this->getSpeakerName());

        if ($source === '') {
            return '--';
        }

        return Str::of($source)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function getSpeakerImageUrl(): ?string
    {
        $image = $this->minister?->image_url;

        if (! is_string($image) || blank($image)) {
            return null;
        }

        $decoded = json_decode($image, true);

        if (is_array($decoded)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            return $decoded[$locale]
                ?? $decoded[$fallback]
                ?? collect($decoded)->first(fn ($item): bool => filled($item))
                ?? null;
        }

        return $image;
    }

    /**
     * Posts actuellement mis en avant sur l'accueil (fenêtre optionnelle de dates + tri).
     *
     * @param  Builder<Post>  $query  Requête Eloquent.
     * @return Builder<Post> Requête filtrée.
     */
    public function scopeFeaturedOnHomeNow(Builder $query): Builder
    {
        return $query
            ->where('featured_on_home', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('featured_from')->orWhere('featured_from', '<=', now());
            })
            ->where(function (Builder $inner): void {
                $inner->whereNull('featured_until')->orWhere('featured_until', '>', now());
            });
    }
}
