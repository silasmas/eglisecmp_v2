<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Gallery;

/**
 * Résout les URLs d'images pour les colonnes Filament (HoverImageColumn).
 */
final class FilamentImageUrl
{
    /**
     * Convertit une valeur image (JSON multilingue, chemin relatif ou URL) en URL absolue.
     *
     * @param  mixed  $value  Champ image brut du modèle.
     * @param  string|null  $locale  Locale préférée ; défaut = locale application.
     * @return string|null URL exploitable par le navigateur, ou null si absent.
     */
    public static function resolve(mixed $value, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = (string) config('app.fallback_locale', 'en');
        $url = SitePublicSerializer::imageUrl($value, $locale, $fallback);

        return $url !== '' ? $url : null;
    }

    /**
     * Première vignette Spatie ou image legacy pour une galerie.
     *
     * @param  Gallery|null  $gallery  Enregistrement galerie.
     * @return string|null URL absolue de l'aperçu, ou null.
     */
    public static function resolveGalleryPreview(?Gallery $gallery): ?string
    {
        if ($gallery === null) {
            return null;
        }

        $media = $gallery->getFirstMedia(Gallery::MEDIA_COLLECTION);

        if ($media !== null) {
            $thumbnail = $media->getUrl('thumbnail');
            $original = $media->getUrl();

            if ($thumbnail !== '') {
                return $thumbnail;
            }

            if ($original !== '') {
                return $original;
            }
        }

        return self::resolve($gallery->image_url);
    }
}
