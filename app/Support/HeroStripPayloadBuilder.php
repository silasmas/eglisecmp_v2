<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DailyVerse;
use App\Models\Event;
use App\Models\ScheduleProgram;
use Illuminate\Support\Carbon;

/**
 * Construit les données des quatre tuiles cliquables du hero et le timing du prochain live.
 */
final class HeroStripPayloadBuilder
{
    /**
     * Calcule la prochaine occurrence d'un créneau récurrent (jour 0 = dimanche).
     *
     * @param  Carbon  $now  Instant de référence (fuseau application).
     * @param  int  $weekday  Jour 0–6.
     * @param  int  $hour  Heure 0–23.
     * @param  int  $minute  Minute 0–59.
     * @return Carbon Date/heure de la prochaine occurrence strictement future ou égale au créneau.
     */
    public static function nextOccurrence(Carbon $now, int $weekday, int $hour, int $minute): Carbon
    {
        $currentDow = (int) $now->dayOfWeek;
        $diff = ($weekday - $currentDow + 7) % 7;
        $candidate = $now->copy()->startOfDay()->addDays($diff)->setTime($hour, $minute, 0);

        if ($candidate <= $now) {
            $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Occurrence du créneau aujourd'hui (null si autre jour de la semaine).
     */
    public static function todayOccurrence(Carbon $now, int $weekday, int $hour, int $minute): ?Carbon
    {
        if ((int) $now->dayOfWeek !== $weekday) {
            return null;
        }

        return $now->copy()->startOfDay()->setTime($hour, $minute, 0);
    }

    /**
     * Extrait l'heure de fin depuis un libellé horaire (ex. « 17h30 - 19h30 »).
     */
    public static function parseEndTimeFromLabel(?string $timeLabel, Carbon $start, int $defaultMinutes = 120): Carbon
    {
        if ($timeLabel !== null && preg_match('/(\d{1,2})[:hH](\d{2})\s*[-–]\s*(\d{1,2})[:hH](\d{2})/u', $timeLabel, $matches)) {
            return $start->copy()->setTime((int) $matches[3], (int) $matches[4], 0);
        }

        return $start->copy()->addMinutes($defaultMinutes);
    }

    /**
     * Retourne le programme live dont la prochaine occurrence est la plus proche, ou null.
     *
     * @return array{at: Carbon, program: ScheduleProgram}|null
     */
    public static function resolveNextLiveProgram(Carbon $now): ?array
    {
        $programs = ScheduleProgram::query()
            ->where('is_active', true)
            ->where('kind', ScheduleProgram::KIND_LIVE)
            ->whereNotNull('weekday')
            ->whereNotNull('live_hour')
            ->orderBy('sort_order')
            ->get();

        $best = null;

        foreach ($programs as $program) {
            $at = self::nextOccurrence(
                $now,
                (int) $program->weekday,
                (int) $program->live_hour,
                (int) ($program->live_minute ?? 0)
            );

            if ($best === null || $at < $best['at']) {
                $best = ['at' => $at, 'program' => $program];
            }
        }

        return $best;
    }

    /**
     * Détecte un live en cours ou calcule le prochain live.
     *
     * @return array{status: string, program: ScheduleProgram|null, start: Carbon|null, end: Carbon|null, nextAt: Carbon|null}
     */
    public static function resolveLiveState(Carbon $now): array
    {
        $programs = ScheduleProgram::query()
            ->where('is_active', true)
            ->where('kind', ScheduleProgram::KIND_LIVE)
            ->whereNotNull('weekday')
            ->whereNotNull('live_hour')
            ->orderBy('sort_order')
            ->get();

        foreach ($programs as $program) {
            $start = self::todayOccurrence(
                $now,
                (int) $program->weekday,
                (int) $program->live_hour,
                (int) ($program->live_minute ?? 0)
            );

            if ($start === null) {
                continue;
            }

            $end = self::parseEndTimeFromLabel((string) ($program->time_label ?? ''), $start, 90);

            if ($now->gte($start) && $now->lt($end)) {
                return [
                    'status' => 'live',
                    'program' => $program,
                    'start' => $start,
                    'end' => $end,
                    'nextAt' => null,
                ];
            }
        }

        $nextLive = self::resolveNextLiveProgram($now);

        return [
            'status' => 'upcoming',
            'program' => $nextLive['program'] ?? null,
            'start' => null,
            'end' => null,
            'nextAt' => $nextLive['at'] ?? null,
        ];
    }

    /**
     * Détecte un programme hebdomadaire en cours ou le prochain.
     *
     * @return array{status: string, program: ScheduleProgram|null, start: Carbon|null, end: Carbon|null, nextAt: Carbon|null}
     */
    public static function resolveWeeklyProgramState(Carbon $now): array
    {
        $programs = ScheduleProgram::query()
            ->where('is_active', true)
            ->where('kind', ScheduleProgram::KIND_WEEKLY)
            ->whereNotNull('weekday')
            ->orderBy('sort_order')
            ->get();

        foreach ($programs as $program) {
            $hour = $program->live_hour ?? 17;
            $minute = $program->live_minute ?? 30;
            $start = self::todayOccurrence($now, (int) $program->weekday, (int) $hour, (int) $minute);

            if ($start === null) {
                continue;
            }

            $end = self::parseEndTimeFromLabel((string) ($program->time_label ?? ''), $start, 120);

            if ($now->gte($start) && $now->lt($end)) {
                return [
                    'status' => 'live',
                    'program' => $program,
                    'start' => $start,
                    'end' => $end,
                    'nextAt' => null,
                ];
            }
        }

        $best = null;

        foreach ($programs as $program) {
            $hour = $program->live_hour ?? 17;
            $minute = $program->live_minute ?? 30;
            $at = self::nextOccurrence($now, (int) $program->weekday, (int) $hour, (int) $minute);

            if ($best === null || $at < $best['at']) {
                $best = ['at' => $at, 'program' => $program];
            }
        }

        return [
            'status' => 'upcoming',
            'program' => $best['program'] ?? null,
            'start' => null,
            'end' => null,
            'nextAt' => $best['at'] ?? null,
        ];
    }

    /**
     * Assemble `liveTiming`, `stripCards` et métadonnées pour la SPA.
     *
     * @param  string  $locale  Locale demandée.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed>
     */
    public static function build(string $locale, string $fallbackLocale): array
    {
        $now = Carbon::now(config('app.timezone'));
        $liveState = self::resolveLiveState($now);
        $weeklyState = self::resolveWeeklyProgramState($now);

        $liveTiming = null;
        $liveProgram = $liveState['program'];

        if ($liveState['status'] === 'live' && $liveState['end'] instanceof Carbon) {
            $liveTiming = [
                'targetIso' => $liveState['end']->toIso8601String(),
                'displayMode' => 'live',
                'daysUntil' => null,
                'status' => 'live',
            ];
        } elseif ($liveState['nextAt'] instanceof Carbon) {
            $secondsUntil = max(0, $liveState['nextAt']->getTimestamp() - $now->getTimestamp());
            $within24h = $secondsUntil < 86400;
            $liveTiming = [
                'targetIso' => $liveState['nextAt']->toIso8601String(),
                'displayMode' => $within24h ? 'countdown' : 'days',
                'daysUntil' => $within24h ? null : max(1, (int) ceil($secondsUntil / 86400)),
                'status' => 'upcoming',
            ];
        }

        $verse = DailyVerse::query()
            ->where('is_active', true)
            ->where('publish_at', '<=', $now)
            ->where('visible_until', '>', $now)
            ->orderByDesc('publish_at')
            ->first();

        $threeWeeksAhead = $now->copy()->addWeeks(3);

        $nextEvent = Event::query()
            ->where('is_active', true)
            ->where('date_debut', '>', $now)
            ->where('date_debut', '<=', $threeWeeksAhead)
            ->orderBy('date_debut')
            ->first();

        $liveSerialized = $liveProgram instanceof ScheduleProgram
            ? SitePublicSerializer::scheduleProgramToPublicArray($liveProgram, $locale, $fallbackLocale)
            : null;

        $liveBanner = '';
        if ($liveProgram instanceof ScheduleProgram) {
            $liveBanner = self::scheduleProgramBannerImage($liveProgram, $locale, $fallbackLocale);
        }

        $liveTile = self::buildLiveTileLabels($liveState, $liveProgram, $liveSerialized, $now);
        $streamEmbed = self::resolveStreamEmbed(
            $liveProgram instanceof ScheduleProgram ? (string) ($liveProgram->link_url ?? '') : ''
        );

        $eventCard = $nextEvent instanceof Event
            ? self::buildEventStripCard($nextEvent, $locale, $fallbackLocale)
            : self::buildWeeklyProgramStripCard($weeklyState, $locale, $fallbackLocale);

        $readingCard = $verse instanceof DailyVerse
            ? self::buildReadingStripCard($verse, $locale, $fallbackLocale)
            : [
                'title' => 'Lecture du jour',
                'subtitle' => 'Touchez pour ouvrir la parole du jour',
                'bannerImage' => '',
                'description' => 'Chaque jour, une parole biblique vous attend ici. Cliquez pour la découvrir, la méditer et la partager avec votre entourage.',
                'reactableKey' => '',
                'status' => 'idle',
                'tilePrimary' => 'Lecture du jour',
                'tileSecondary' => 'Cliquez ici pour découvrir la parole du jour ✨',
            ];

        $stripCards = [
            'live' => array_merge([
                'title' => $liveTile['modalTitle'],
                'subtitle' => $liveTile['modalSubtitle'],
                'bannerImage' => $liveBanner,
                'description' => is_array($liveSerialized) ? (string) ($liveSerialized['description'] ?? '') : '',
                'reactableKey' => is_array($liveSerialized) ? (string) ($liveSerialized['reactableKey'] ?? '') : '',
                'status' => $liveState['status'],
                'tilePrimary' => $liveTile['tilePrimary'],
                'tileSecondary' => $liveTile['tileSecondary'],
            ], $streamEmbed),
            'event' => $eventCard,
            'reading' => $readingCard,
            'location' => self::buildLocationStripCard(),
        ];

        return [
            'liveTiming' => $liveTiming,
            'stripCards' => $stripCards,
        ];
    }

    /**
     * Libellés dynamiques pour la tuile live du hero.
     *
     * @param  array<string, mixed>  $liveState  État live calculé.
     * @param  array<string, mixed>|null  $liveSerialized  Programme sérialisé.
     * @return array{tilePrimary: string, tileSecondary: string, modalTitle: string, modalSubtitle: string}
     */
    private static function buildLiveTileLabels(
        array $liveState,
        ?ScheduleProgram $liveProgram,
        ?array $liveSerialized,
        Carbon $now,
    ): array {
        $name = is_array($liveSerialized) ? (string) ($liveSerialized['name'] ?? 'Live') : 'Live';
        $timeLabel = $liveProgram instanceof ScheduleProgram ? (string) ($liveProgram->time_label ?? '') : '';
        $dayLabel = $liveProgram instanceof ScheduleProgram ? (string) ($liveProgram->day_label ?? '') : '';

        if ($liveState['status'] === 'live') {
            $endLabel = $liveState['end'] instanceof Carbon
                ? $liveState['end']->locale('fr')->format('H\\hi')
                : '';

            return [
                'tilePrimary' => 'Live en cours',
                'tileSecondary' => $endLabel !== ''
                    ? "{$name} · jusqu'à {$endLabel}"
                    : $name,
                'modalTitle' => 'Live en cours',
                'modalSubtitle' => $name.($timeLabel !== '' ? " · {$timeLabel}" : ''),
            ];
        }

        $nextAt = $liveState['nextAt'] ?? null;
        $tilePrimary = 'Prochain live';
        $tileSecondary = $name;

        if ($nextAt instanceof Carbon) {
            $secondsUntil = max(0, $nextAt->getTimestamp() - $now->getTimestamp());

            if ($secondsUntil < 86400) {
                $hours = (int) floor($secondsUntil / 3600);
                $minutes = (int) floor(($secondsUntil % 3600) / 60);
                $tilePrimary = 'Prochain live dans '.sprintf('%02d:%02d', $hours, $minutes);
            } else {
                $days = max(1, (int) ceil($secondsUntil / 86400));
                $tilePrimary = "Prochain live dans {$days} jour".($days > 1 ? 's' : '');
            }

            $tileSecondary = ($dayLabel !== '' ? $dayLabel : $nextAt->locale('fr')->translatedFormat('l'))
                .' · '.($timeLabel !== '' ? $timeLabel : $nextAt->format('H\\hi'));
        }

        return [
            'tilePrimary' => $tilePrimary,
            'tileSecondary' => $tileSecondary,
            'modalTitle' => $dayLabel !== '' ? $dayLabel : $name,
            'modalSubtitle' => $tileSecondary,
        ];
    }

    /**
     * Carte modale « événement » à partir du prochain événement futur.
     *
     * @return array<string, mixed>
     */
    private static function buildEventStripCard(Event $event, string $locale, string $fallbackLocale): array
    {
        $row = SitePublicSerializer::eventToPublicArray($event, $locale, $fallbackLocale);
        $bannerRaw = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallbackLocale);

        return [
            'title' => (string) ($row['title'] ?? 'Événement'),
            'subtitle' => (string) ($row['date'] ?? '').' · '.(string) ($row['time'] ?? ''),
            'bannerImage' => $bannerRaw,
            'description' => (string) ($row['description'] ?? ''),
            'reactableKey' => '',
            'status' => 'upcoming',
            'tilePrimary' => (string) ($row['title'] ?? 'Prochain événement'),
            'tileSecondary' => (string) ($row['date'] ?? '').' · '.(string) ($row['time'] ?? ''),
        ];
    }

    /**
     * Carte modale « lecture du jour ».
     *
     * @return array<string, mixed>
     */
    private static function buildReadingStripCard(DailyVerse $verse, string $locale, string $fallbackLocale): array
    {
        $row = SitePublicSerializer::dailyVerseToPublicArray($verse, $locale, $fallbackLocale);
        $bannerImage = SitePublicSerializer::imageUrl($verse->image_url ?? [], $locale, $fallbackLocale);
        $excerpt = (string) ($row['excerpt'] ?? '');

        return [
            'title' => 'Lecture du jour',
            'subtitle' => $excerpt,
            'bannerImage' => $bannerImage,
            'description' => (string) ($row['text'] ?? ''),
            'reactableKey' => (string) ($row['reactableKey'] ?? ''),
            'reference' => (string) ($row['reference'] ?? ''),
            'status' => 'upcoming',
            'tilePrimary' => 'Lecture du jour',
            'tileSecondary' => $excerpt !== '' ? $excerpt : (string) ($row['reference'] ?? ''),
        ];
    }

    /**
     * Carte hero « programme hebdomadaire » avec état en cours / prochain.
     *
     * @param  array<string, mixed>  $weeklyState  État calculé.
     * @return array<string, mixed>
     */
    private static function buildWeeklyProgramStripCard(array $weeklyState, string $locale, string $fallbackLocale): array
    {
        $program = $weeklyState['program'] ?? null;

        if (! $program instanceof ScheduleProgram) {
            return [
                'title' => 'Prochain rendez-vous',
                'subtitle' => '',
                'bannerImage' => '',
                'description' => 'Consultez nos programmes hebdomadaires.',
                'reactableKey' => '',
                'status' => 'idle',
                'tilePrimary' => 'Programme de la semaine',
                'tileSecondary' => 'Consultez nos rendez-vous',
            ];
        }

        $serialized = SitePublicSerializer::scheduleProgramToPublicArray($program, $locale, $fallbackLocale);
        $name = (string) ($serialized['name'] ?? 'Rendez-vous hebdomadaire');
        $timeLabel = (string) ($program->time_label ?? $serialized['time'] ?? '');
        $dayLabel = (string) ($program->day_label ?? '');

        if ($weeklyState['status'] === 'live') {
            $endLabel = $weeklyState['end'] instanceof Carbon
                ? $weeklyState['end']->locale('fr')->format('H\\hi')
                : '';

            return [
                'title' => $name,
                'subtitle' => $timeLabel,
                'bannerImage' => self::scheduleProgramBannerImage($program, $locale, $fallbackLocale),
                'description' => (string) ($serialized['description'] ?? ''),
                'reactableKey' => (string) ($serialized['reactableKey'] ?? ''),
                'status' => 'live',
                'tilePrimary' => 'Programme en cours',
                'tileSecondary' => $endLabel !== ''
                    ? "{$name} · jusqu'à {$endLabel}"
                    : $name,
            ];
        }

        $nextAt = $weeklyState['nextAt'] ?? null;
        $tileSecondary = $dayLabel;

        if ($nextAt instanceof Carbon) {
            $tileSecondary = $dayLabel !== ''
                ? $dayLabel.' · '.$nextAt->locale('fr')->translatedFormat('d M')
                : $nextAt->locale('fr')->translatedFormat('l d M').($timeLabel !== '' ? " · {$timeLabel}" : '');
        }

        return [
            'title' => $name,
            'subtitle' => $tileSecondary,
            'bannerImage' => self::scheduleProgramBannerImage($program, $locale, $fallbackLocale),
            'description' => (string) ($serialized['description'] ?? ''),
            'reactableKey' => (string) ($serialized['reactableKey'] ?? ''),
            'status' => 'upcoming',
            'tilePrimary' => $name,
            'tileSecondary' => $tileSecondary,
        ];
    }

    /**
     * Image de bannière modale depuis un programme d'antenne, sans valeur de substitution.
     */
    private static function scheduleProgramBannerImage(ScheduleProgram $program, string $locale, string $fallbackLocale): string
    {
        $banner = SitePublicSerializer::imageUrl($program->banner_image ?? [], $locale, $fallbackLocale);

        if ($banner === '') {
            $banner = SitePublicSerializer::imageUrl($program->image_url ?? [], $locale, $fallbackLocale);
        }

        return $banner;
    }

    /**
     * Carte modale « nous trouver » (texte configurable).
     *
     * @return array<string, mixed>
     */
    private static function buildLocationStripCard(): array
    {
        $block = (array) config('site_public.hero_strip.location', []);

        $title = is_string($block['title'] ?? null) ? $block['title'] : 'Nous trouver';
        $summary = is_string($block['summary'] ?? null) ? $block['summary'] : '';
        $description = is_string($block['description'] ?? null) ? $block['description'] : '';
        $banner = is_string($block['banner_image'] ?? null) ? $block['banner_image'] : '';
        $mapsUrl = is_string($block['maps_url'] ?? null) ? $block['maps_url'] : '';

        $banner = $banner !== ''
            ? SitePublicSerializer::normalizePublicImageUrl($banner)
            : '';

        if ($description === '' && $summary !== '') {
            $description = $summary;
        }

        return [
            'title' => $title,
            'subtitle' => $summary,
            'bannerImage' => $banner,
            'description' => $description,
            'reactableKey' => '',
            'mapUrl' => $mapsUrl,
            'status' => 'idle',
            'tilePrimary' => $title,
            'tileSecondary' => 'Cliquez pour voir l\'église sur la carte',
        ];
    }

    /**
     * Résout les URLs d'intégration YouTube / Facebook pour un live.
     *
     * @return array{linkUrl: string, embedUrl: string, embedKind: string}
     */
    private static function resolveStreamEmbed(string $linkUrl): array
    {
        $linkUrl = trim($linkUrl);

        if ($linkUrl === '') {
            return [
                'linkUrl' => '',
                'embedUrl' => '',
                'embedKind' => 'none',
            ];
        }

        $youtubeEmbed = SitePublicSerializer::youtubeEmbedUrlFromLink($linkUrl);

        if ($youtubeEmbed !== '') {
            return [
                'linkUrl' => $linkUrl,
                'embedUrl' => $youtubeEmbed,
                'embedKind' => 'youtube',
            ];
        }

        if (str_contains(strtolower($linkUrl), 'facebook.com')) {
            return [
                'linkUrl' => $linkUrl,
                'embedUrl' => 'https://www.facebook.com/plugins/video.php?href='.urlencode($linkUrl).'&show_text=false&width=560',
                'embedKind' => 'facebook',
            ];
        }

        return [
            'linkUrl' => $linkUrl,
            'embedUrl' => '',
            'embedKind' => 'none',
        ];
    }
}
