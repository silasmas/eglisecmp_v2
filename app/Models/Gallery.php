<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use NoteBrainsLab\FilamentMenuManager\Concerns\HasMenuItems;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Gallery extends Model implements HasMedia
{
    use HasFactory;
    use HasMenuItems;
    use InteractsWithMedia;

    public const MEDIA_COLLECTION = 'gallery';

    protected $fillable = [
        'image_url',
        'description',
        'is_active',
        'post_id',
        'project_id',
    ];

    protected function casts(): array
    {
        return [
            'image_url' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->performOnCollections(self::MEDIA_COLLECTION)
            ->width(400)
            ->height(400)
            ->nonQueued();
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getMenuLabel(): string
    {
        $desc = $this->description;
        if (is_array($desc)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');
            $text = (string) ($desc[$locale] ?? $desc[$fallback] ?? collect($desc)->first() ?? '');
            if ($text !== '') {
                return Str::limit($text, 80);
            }
        }

        return 'Galerie #'.$this->getKey();
    }
}
