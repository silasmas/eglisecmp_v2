<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\DailyVerse;
use App\Models\ScheduleProgram;
use App\Support\HeroStripPayloadBuilder;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Regroupe verset du jour et créneaux « live » pour le bandeau du hero.
 */
class PublicHeroMetaController extends Controller
{
    /**
     * Retourne le verset courant et la liste des créneaux live récurrents.
     *
     * @param  Request  $request  Requête HTTP.
     * @return JsonResponse Clés `verse` (nullable) et `liveSlots` (tableau).
     */
    public function show(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $verse = DailyVerse::query()
            ->where('is_active', true)
            ->where('publish_at', '<=', now())
            ->where('visible_until', '>', now())
            ->orderByDesc('publish_at')
            ->first();

        $liveSlots = ScheduleProgram::query()
            ->where('is_active', true)
            ->where('kind', ScheduleProgram::KIND_LIVE)
            ->whereNotNull('weekday')
            ->whereNotNull('live_hour')
            ->orderBy('sort_order')
            ->get()
            ->map(static function (ScheduleProgram $program) use ($locale, $fallback): array {
                $dayLabel = (string) ($program->day_label ?? '');
                $title = SitePublicSerializer::scheduleProgramToPublicArray($program, $locale, $fallback);

                return [
                    'weekday' => (int) $program->weekday,
                    'hour' => (int) $program->live_hour,
                    'minute' => (int) ($program->live_minute ?? 0),
                    'label' => $dayLabel !== '' ? $dayLabel : (string) ($title['name'] ?? 'Live'),
                    'subtitle' => (string) ($title['description'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $strip = HeroStripPayloadBuilder::build($locale, $fallback);
        $reactionKeys = (array) config('site_public.reaction_keys', []);

        return response()->json([
            'data' => [
                'verse' => $verse
                    ? SitePublicSerializer::dailyVerseToPublicArray($verse, $locale, $fallback)
                    : null,
                'liveSlots' => $liveSlots,
                'liveTiming' => $strip['liveTiming'],
                'stripCards' => $strip['stripCards'],
                'reactionKeys' => $reactionKeys,
            ],
        ]);
    }
}
