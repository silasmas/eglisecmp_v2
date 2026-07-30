<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Minister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fullname',
        'image_url',
        'bio',
        'is_titular',
        'is_active',
        'contact',
        'type',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'youtube_url',
    ];

    protected function casts(): array
    {
        return [
            'fullname' => 'string',
            'image_url' => 'string',
            'bio' => 'string',
            'is_titular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Compte Filament lié à ce pasteur.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Plages horaires de réception pour les rendez-vous.
     *
     * @return HasMany<MinisterReceptionSchedule, $this>
     */
    public function receptionSchedules(): HasMany
    {
        return $this->hasMany(MinisterReceptionSchedule::class);
    }

    /**
     * Rendez-vous assignés à ce pasteur.
     *
     * @return HasMany<SiteInquiry, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(SiteInquiry::class)->where('kind', SiteInquiry::KIND_APPOINTMENT);
    }
}
