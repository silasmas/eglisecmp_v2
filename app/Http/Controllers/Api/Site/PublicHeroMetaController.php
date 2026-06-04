<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\DailyVerse;
use App\Models\ScheduleProgram;
use App\Services\YoutubeLiveStatusService;
use App\Support\HeroStripPayloadBuilder;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
    public function show(Request $request, YoutubeLiveStatusService $youtubeLive): JsonResponse
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
        $youtube = $youtubeLive->current();
        $payload = $this->mergeYoutubeLive($strip, $youtube);

        return response()->json([
            'data' => [
                'verse' => $verse
                    ? SitePublicSerializer::dailyVerseToPublicArray($verse, $locale, $fallback)
                    : null,
                'liveSlots' => $liveSlots,
                'liveTiming' => $payload['liveTiming'],
                'stripCards' => $payload['stripCards'],
                'youtubeLive' => $youtube,
                'reactionKeys' => $reactionKeys,
            ],
        ]);
    }

    /**
     * Force l’état « live » du bandeau lorsque YouTube diffuse en direct.
     *
     * @param  array<string, mixed>  $strip
     * @param  array<string, mixed>|null  $youtube
     * @return array<string, mixed>
     */
    private function mergeYoutubeLive(array $strip, ?array $youtube): array
    {
        if ($youtube === null || ! isset($strip['stripCards']['live']) || ! is_array($strip['stripCards']['live'])) {
            return $strip;
        }

        $end = Carbon::now(config('app.timezone'))->addHours(3);
        $strip['stripCards']['live']['status'] = 'live';
        $strip['stripCards']['live']['embedUrl'] = (string) ($youtube['embedUrl'] ?? '');
        $strip['stripCards']['live']['embedKind'] = 'youtube';
        $strip['stripCards']['live']['linkUrl'] = (string) ($youtube['watchUrl'] ?? '');
        $strip['stripCards']['live']['tilePrimary'] = 'Live YouTube';
        $strip['stripCards']['live']['tileSecondary'] = (string) ($youtube['title'] ?? 'En direct');
        $strip['stripCards']['live']['modalBadge'] = 'En direct';
        $strip['stripCards']['live']['modalBadgeTone'] = 'live';

        $strip['liveTiming'] = [
            'targetIso' => $end->toIso8601String(),
            'startIso' => Carbon::now(config('app.timezone'))->toIso8601String(),
            'endIso' => $end->toIso8601String(),
            'displayMode' => 'live',
            'daysUntil' => null,
            'status' => 'live',
            'programName' => (string) ($youtube['title'] ?? 'Live YouTube'),
            'scheduledLabel' => 'Diffusion en cours sur YouTube',
            'timeLabel' => '',
            'dayLabel' => '',
        ];

        return $strip;
    }
}
