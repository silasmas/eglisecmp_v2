<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Événement public (culte, conférence, célébration) affiché sur le site.
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'type',
        'lieu',
        'orateur',
        'date_debut',
        'date_fin',
        'is_active',
        'theme',
        'references',
        'image_url',
        'est_a_la_une',
        'featured_from',
        'featured_until',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'designation' => 'array',
            'theme' => 'array',
            'references' => 'array',
            'description' => 'array',
            'image_url' => 'array',
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'featured_from' => 'datetime',
            'featured_until' => 'datetime',
            'is_active' => 'boolean',
            'est_a_la_une' => 'boolean',
        ];
    }

    /**
     * Publications liées à cet événement.
     *
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Événements actuellement mis en avant (modale + bouton flottant).
     *
     * @param  Builder<Event>  $query  Requête Eloquent.
     * @return Builder<Event> Requête filtrée.
     */
    public function scopeFeaturedSpotlightNow(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('est_a_la_une', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('featured_from')->orWhere('featured_from', '<=', now());
            })
            ->where(function (Builder $inner): void {
                $inner->whereNull('featured_until')->orWhere('featured_until', '>', now());
            });
    }

    /**
     * Indique si l'événement est dans sa fenêtre de mise en avant.
     */
    public function isFeaturedSpotlightNow(): bool
    {
        if (! $this->is_active || ! $this->est_a_la_une) {
            return false;
        }

        $now = now();

        if ($this->featured_from !== null && $this->featured_from->gt($now)) {
            return false;
        }

        if ($this->featured_until !== null && $this->featured_until->lte($now)) {
            return false;
        }

        return true;
    }
}
