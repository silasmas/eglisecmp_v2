<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Minister;
use App\Models\MinisterReceptionSchedule;
use App\Models\SiteInquiry;
use App\Support\SitePublicSerializer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcule les pasteurs disponibles, dates et créneaux pour les rendez-vous publics.
 */
final class AppointmentAvailabilityService
{
    private const HORIZON_DAYS = 60;

    /**
     * Liste les pasteurs actifs ayant au moins un horaire de réception actif.
     *
     * @param  string  $locale  Locale pour les libellés multilingues.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return list<array<string, mixed>>
     */
    public function ministersForBooking(string $locale, string $fallbackLocale): array
    {
        $ministerIds = MinisterReceptionSchedule::query()
            ->where('is_active', true)
            ->distinct()
            ->pluck('minister_id');

        if ($ministerIds->isEmpty()) {
            return [];
        }

        return Minister::query()
            ->whereIn('id', $ministerIds)
            ->where('is_active', true)
            ->orderBy('fullname')
            ->get()
            ->map(fn (Minister $minister): array => $this->serializeMinister($minister, $locale, $fallbackLocale))
            ->values()
            ->all();
    }

    /**
     * Dates (Y-m-d) sur lesquelles le pasteur a au moins un créneau libre.
     *
     * @return list<string>
     */
    public function availableDatesForMinister(int $ministerId): array
    {
        $dates = [];
        $today = Carbon::today();

        for ($offset = 0; $offset < self::HORIZON_DAYS; $offset++) {
            $date = $today->copy()->addDays($offset);
            if ($this->slotsForMinisterOnDate($ministerId, $date)->isNotEmpty()) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    /**
     * Créneaux disponibles pour un pasteur à une date donnée.
     *
     * @return list<array{starts_at: string, ends_at: string, label: string}>
     */
    public function slotsForMinisterOnDate(int $ministerId, Carbon $date): Collection
    {
        $dayOfWeek = $date->dayOfWeekIso;
        $schedules = MinisterReceptionSchedule::query()
            ->where('minister_id', $ministerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $booked = SiteInquiry::query()
            ->where('kind', SiteInquiry::KIND_APPOINTMENT)
            ->where('minister_id', $ministerId)
            ->whereDate('preferred_at', $date->format('Y-m-d'))
            ->whereNotIn('appointment_status', [SiteInquiry::STATUS_DECLINED])
            ->pluck('preferred_at')
            ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'))
            ->all();

        $slots = collect();

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($date->format('Y-m-d').' '.$schedule->starts_at);
            $end = Carbon::parse($date->format('Y-m-d').' '.$schedule->ends_at);
            $duration = max(15, (int) $schedule->slot_minutes);

            for ($cursor = $start->copy(); $cursor->copy()->addMinutes($duration) <= $end; $cursor->addMinutes($duration)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);

                if ($cursor->isPast()) {
                    continue;
                }

                $key = $cursor->format('Y-m-d H:i:s');
                if (in_array($key, $booked, true)) {
                    continue;
                }

                $slots->push([
                    'starts_at' => $cursor->toIso8601String(),
                    'ends_at' => $slotEnd->toIso8601String(),
                    'label' => $cursor->format('H:i').' – '.$slotEnd->format('H:i'),
                ]);
            }
        }

        return $slots
            ->unique(fn (array $slot): string => $slot['starts_at'])
            ->sortBy('starts_at')
            ->values();
    }

    /**
     * Vérifie qu’un créneau est encore réservable.
     */
    public function slotIsAvailable(int $ministerId, Carbon $startsAt): bool
    {
        if ($startsAt->isPast()) {
            return false;
        }

        $matching = $this->slotsForMinisterOnDate($ministerId, $startsAt->copy()->startOfDay())
            ->first(fn (array $slot): bool => Carbon::parse($slot['starts_at'])->equalTo($startsAt));

        return $matching !== null;
    }

    /**
     * Retourne le bureau associé au créneau pasteur (jour + heure exacte).
     */
    public function resolveBureauForSlot(int $ministerId, Carbon $startsAt): ?int
    {
        $dayOfWeek = $startsAt->dayOfWeekIso;
        $schedules = MinisterReceptionSchedule::query()
            ->where('minister_id', $ministerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($startsAt->format('Y-m-d').' '.$schedule->starts_at);
            $end = Carbon::parse($startsAt->format('Y-m-d').' '.$schedule->ends_at);
            $duration = max(15, (int) $schedule->slot_minutes);

            for ($cursor = $start->copy(); $cursor->copy()->addMinutes($duration) <= $end; $cursor->addMinutes($duration)) {
                if ($cursor->equalTo($startsAt)) {
                    return $schedule->bureau_id !== null ? (int) $schedule->bureau_id : null;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMinister(Minister $minister, string $locale, string $fallbackLocale): array
    {
        return [
            'id' => $minister->id,
            'fullname' => SitePublicSerializer::text($minister->fullname, $locale, $fallbackLocale),
            'image_url' => SitePublicSerializer::imageUrl($minister->image_url, $locale, $fallbackLocale),
            'bio' => SitePublicSerializer::text($minister->bio ?? '', $locale, $fallbackLocale),
        ];
    }
}
