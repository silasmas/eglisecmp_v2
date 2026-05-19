<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'is_active' => 'boolean',
            'est_a_la_une' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
