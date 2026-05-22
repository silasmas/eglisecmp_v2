<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MinisterReceptionSchedule;
use Illuminate\Support\Carbon;

/**
 * Vérifie les chevauchements de créneaux de réception (par bureau et jour).
 */
final class MinisterReceptionScheduleGuard
{
    /**
     * Indique si la plage chevauche une autre plage active sur le même bureau et jour.
     *
     * @param  int  $bureauId  Identifiant bureau.
     * @param  int  $dayOfWeek  Jour ISO 1–7.
     * @param  string  $startsAt  Heure début (H:i ou H:i:s).
     * @param  string  $endsAt  Heure fin (H:i ou H:i:s).
     * @param  int|null  $exceptId  Plage en cours d’édition à exclure.
     */
    public static function bureauSlotIsTaken(
        int $bureauId,
        int $dayOfWeek,
        string $startsAt,
        string $endsAt,
        ?int $exceptId = null,
    ): bool {
        return self::findConflictingSchedule($bureauId, $dayOfWeek, $startsAt, $endsAt, $exceptId) !== null;
    }

    /**
     * Retourne la première plage en conflit ou null.
     */
    public static function findConflictingSchedule(
        int $bureauId,
        int $dayOfWeek,
        string $startsAt,
        string $endsAt,
        ?int $exceptId = null,
    ): ?MinisterReceptionSchedule {
        $start = self::timeToMinutes($startsAt);
        $end = self::timeToMinutes($endsAt);

        if ($end <= $start) {
            return null;
        }

        $query = MinisterReceptionSchedule::query()
            ->with('minister')
            ->where('bureau_id', $bureauId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        foreach ($query->get() as $schedule) {
            $otherStart = self::timeToMinutes((string) $schedule->starts_at);
            $otherEnd = self::timeToMinutes((string) $schedule->ends_at);

            if ($start < $otherEnd && $end > $otherStart) {
                return $schedule;
            }
        }

        return null;
    }

    /**
     * Message d’erreur lisible pour un conflit de bureau.
     */
    public static function conflictMessage(?MinisterReceptionSchedule $conflict): string
    {
        if ($conflict === null) {
            return 'Ce créneau est déjà occupé dans ce bureau pour ce jour.';
        }

        $ministerName = is_string($conflict->minister?->fullname)
            ? trim($conflict->minister->fullname)
            : '';

        $slotLabel = Carbon::parse($conflict->starts_at)->format('H:i')
            .' – '
            .Carbon::parse($conflict->ends_at)->format('H:i');

        if ($ministerName !== '') {
            return "Ce créneau est déjà réservé dans ce bureau ({$slotLabel}, pasteur {$ministerName}).";
        }

        return "Ce créneau est déjà réservé dans ce bureau ({$slotLabel}).";
    }

    /**
     * Convertit une heure SQL en minutes depuis minuit.
     */
    private static function timeToMinutes(string $time): int
    {
        $parsed = Carbon::parse($time);

        return ((int) $parsed->format('H') * 60) + (int) $parsed->format('i');
    }
}
