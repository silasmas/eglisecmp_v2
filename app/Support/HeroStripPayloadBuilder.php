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
     * Assemble `liveTiming`, `stripCards` et métadonnées pour la SPA.
     *
     * @param  string  $locale  Locale demandée.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed>
     */
    public static function build(string $locale, string $fallbackLocale): array
    {
        $now = Carbon::now(config('app.timezone'));
        $nextLive = self::resolveNextLiveProgram($now);

        $liveTiming = null;

        if ($nextLive !== null) {
            $secondsUntil = max(0, $nextLive['at']->getTimestamp() - $now->getTimestamp());
            $within24h = $secondsUntil < 86400;
            $liveTiming = [
                'targetIso' => $nextLive['at']->toIso8601String(),
                'displayMode' => $within24h ? 'countdown' : 'days',
                'daysUntil' => $within24h ? null : max(1, (int) ceil($secondsUntil / 86400)),
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

        $liveProgram = $nextLive !== null ? $nextLive['program'] : null;
        $liveSerialized = $liveProgram instanceof ScheduleProgram
          ? SitePublicSerializer::scheduleProgramToPublicArray($liveProgram, $locale, $fallbackLocale)
          : null;

        $liveBanner = '';
        if ($liveProgram instanceof ScheduleProgram) {
            $liveBanner = SitePublicSerializer::imageUrl($liveProgram->banner_image ?? [], $locale, $fallbackLocale);

            if ($liveBanner === '') {
                $liveBanner = SitePublicSerializer::imageUrl($liveProgram->image_url ?? [], $locale, $fallbackLocale);
            }
        }

        $dayLabel = $liveProgram !== null ? (string) ($liveProgram->day_label ?? '') : '';
        $liveTitle = is_array($liveSerialized)
          ? (string) ($liveSerialized['name'] ?? 'Prochain live')
          : 'Prochain live';
        $liveSubtitle = is_array($liveSerialized)
          ? (string) ($liveSerialized['description'] ?? '')
          : '';

        $stripCards = [
            'live' => [
                'title' => $dayLabel !== '' ? $dayLabel : $liveTitle,
                'subtitle' => $liveSubtitle,
                'bannerImage' => $liveBanner,
                'description' => is_array($liveSerialized) ? (string) ($liveSerialized['description'] ?? '') : '',
                'reactableKey' => is_array($liveSerialized) ? (string) ($liveSerialized['reactableKey'] ?? '') : '',
            ],
            'event' => $nextEvent instanceof Event
                ? self::buildEventStripCard($nextEvent, $locale, $fallbackLocale)
                : self::buildWeeklyProgramStripCard($now, $locale, $fallbackLocale),
            'reading' => $verse instanceof DailyVerse
              ? self::buildReadingStripCard($verse, $locale, $fallbackLocale)
              : [
                  'title' => 'Lecture du jour',
                  'subtitle' => '',
                  'bannerImage' => '',
                  'description' => 'Publiez une entrée « Lecture du jour » dans l’admin (fenêtre 24 h).',
                  'reactableKey' => '',
              ],
            'location' => self::buildLocationStripCard(),
        ];

        return [
            'liveTiming' => $liveTiming,
            'stripCards' => $stripCards,
        ];
    }

    /**
     * Carte modale « événement » à partir du prochain événement futur.
     *
     * @return array<string, string>
     */
    private static function buildEventStripCard(?Event $event, string $locale, string $fallbackLocale): array
    {
        if (! $event instanceof Event) {
            return [
                'title' => 'Prochain événement',
                'subtitle' => '',
                'bannerImage' => '',
                'description' => 'Bientôt annoncé.',
                'reactableKey' => '',
            ];
        }

        $row = SitePublicSerializer::eventToPublicArray($event, $locale, $fallbackLocale);
        $bannerRaw = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallbackLocale);

        return [
            'title' => (string) ($row['title'] ?? 'Événement'),
            'subtitle' => (string) ($row['date'] ?? '').' · '.(string) ($row['time'] ?? ''),
            'bannerImage' => $bannerRaw,
            'description' => (string) ($row['description'] ?? ''),
            'reactableKey' => '',
        ];
    }

    /**
     * Carte modale « lecture du jour ».
     *
     * @return array<string, string>
     */
    private static function buildReadingStripCard(DailyVerse $verse, string $locale, string $fallbackLocale): array
    {
        $row = SitePublicSerializer::dailyVerseToPublicArray($verse, $locale, $fallbackLocale);

        $bannerImage = SitePublicSerializer::imageUrl($verse->image_url ?? [], $locale, $fallbackLocale);

        return [
            'title' => 'Lecture du jour',
            'subtitle' => (string) ($row['excerpt'] ?? ''),
            'bannerImage' => $bannerImage,
            'description' => (string) ($row['text'] ?? ''),
            'reactableKey' => (string) ($row['reactableKey'] ?? ''),
            'reference' => (string) ($row['reference'] ?? ''),
        ];
    }

    /**
     * Carte hero « événement » basée sur le prochain programme hebdomadaire (si aucun événement sous 3 semaines).
     *
     * @return array<string, string>
     */
    private static function buildWeeklyProgramStripCard(Carbon $now, string $locale, string $fallbackLocale): array
    {
        $programs = ScheduleProgram::query()
            ->where('is_active', true)
            ->where('kind', ScheduleProgram::KIND_WEEKLY)
            ->whereNotNull('weekday')
            ->orderBy('sort_order')
            ->get();

        $best = null;

        foreach ($programs as $program) {
            $hour = $program->live_hour ?? 17;
            $minute = $program->live_minute ?? 30;
            $at = self::nextOccurrence($now, (int) $program->weekday, (int) $hour, (int) $minute);

            if ($best === null || $at < $best['at']) {
                $best = ['at' => $at, 'program' => $program];
            }
        }

        if ($best === null) {
            $fallback = $programs->first();

            if ($fallback instanceof ScheduleProgram) {
                $serialized = SitePublicSerializer::scheduleProgramToPublicArray($fallback, $locale, $fallbackLocale);

                return [
                    'title' => 'Prochain rendez-vous',
                    'subtitle' => (string) (($serialized['day'] ?? '').' · '.($serialized['time'] ?? '')),
                    'bannerImage' => self::scheduleProgramBannerImage($fallback, $locale, $fallbackLocale),
                    'description' => (string) ($serialized['description'] ?? ''),
                    'reactableKey' => (string) ($serialized['reactableKey'] ?? ''),
                ];
            }

            return [
                'title' => 'Prochain événement',
                'subtitle' => '',
                'bannerImage' => '',
                'description' => 'Consultez nos programmes hebdomadaires.',
                'reactableKey' => '',
            ];
        }

        $serialized = SitePublicSerializer::scheduleProgramToPublicArray($best['program'], $locale, $fallbackLocale);
        $dayLabel = (string) ($best['program']->day_label ?? '');

        return [
            'title' => (string) ($serialized['name'] ?? 'Rendez-vous hebdomadaire'),
            'subtitle' => $dayLabel !== ''
                ? $dayLabel.' · '.$best['at']->locale('fr')->translatedFormat('d M')
                : $best['at']->locale('fr')->translatedFormat('l d M').' · '.(string) ($serialized['time'] ?? ''),
            'bannerImage' => self::scheduleProgramBannerImage($best['program'], $locale, $fallbackLocale),
            'description' => (string) ($serialized['description'] ?? ''),
            'reactableKey' => (string) ($serialized['reactableKey'] ?? ''),
        ];
    }

    /**
     * Image de bannière modale depuis un programme d'antenne, sans valeur de substitution.
     *
     * @param  ScheduleProgram  $program  Programme source.
     * @param  string  $locale  Locale demandée.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return string URL ou chaîne vide.
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
     * @return array<string, string>
     */
    private static function buildLocationStripCard(): array
    {
        $block = (array) config('site_public.hero_strip.location', []);

        $title = is_string($block['title'] ?? null) ? $block['title'] : 'Nous trouver';
        $summary = is_string($block['summary'] ?? null) ? $block['summary'] : '';
        $description = is_string($block['description'] ?? null) ? $block['description'] : '';
        $banner = is_string($block['banner_image'] ?? null) ? $block['banner_image'] : '';

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
        ];
    }
}
