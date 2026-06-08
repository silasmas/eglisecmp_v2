<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ScheduleProgram;

/**
 * Résout l'image de vignette d'un programme d'antenne (dashboard ou défaut par jour).
 */
final class ScheduleProgramBannerResolver
{
    /**
     * Retourne l'URL publique de la vignette : bannière admin, image, puis défaut hebdomadaire.
     *
     * @param  ScheduleProgram  $program  Programme source.
     * @param  string  $locale  Locale demandée.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return string URL absolue ou chemin public, vide si aucune source.
     */
    public static function resolve(ScheduleProgram $program, string $locale, string $fallbackLocale): string
    {
        $banner = SitePublicSerializer::imageUrl($program->banner_image ?? [], $locale, $fallbackLocale);

        if ($banner !== '') {
            return $banner;
        }

        $banner = SitePublicSerializer::imageUrl($program->image_url ?? [], $locale, $fallbackLocale);

        if ($banner !== '') {
            return $banner;
        }

        if ($program->weekday === null) {
            return '';
        }

        $defaults = (array) config('site_public.weekly_program_default_banners', []);
        $path = $defaults[(int) $program->weekday] ?? null;

        if (! is_string($path) || $path === '') {
            return '';
        }

        return SitePublicSerializer::normalizePublicImageUrl($path);
    }
}
