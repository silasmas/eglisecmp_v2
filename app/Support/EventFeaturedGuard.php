<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier : un seul événement en avant, fenêtres de dates cohérentes.
 */
final class EventFeaturedGuard
{
    /**
     * Désactive la mise en avant sur tous les autres événements.
     *
     * @param  Event  $event  Événement conservé en avant.
     */
    public static function ensureSingleFeaturedExcept(Event $event): void
    {
        Event::query()
            ->where('est_a_la_une', true)
            ->whereKeyNot($event->getKey())
            ->update(['est_a_la_une' => false]);
    }

    /**
     * Valide les dates de mise en avant d'un événement.
     *
     * @param  Event  $event  Événement à contrôler.
     *
     * @throws ValidationException Si les dates sont incohérentes ou chevauchent un autre événement en avant.
     */
    public static function assertFeaturedWindowValid(Event $event): void
    {
        if (! $event->est_a_la_une) {
            return;
        }

        if (
            $event->featured_from !== null
            && $event->featured_until !== null
            && $event->featured_from->gte($event->featured_until)
        ) {
            throw ValidationException::withMessages([
                'featured_until' => 'La fin de mise en avant doit être postérieure au début.',
            ]);
        }

        $others = Event::query()
            ->where('est_a_la_une', true)
            ->whereKeyNot($event->getKey())
            ->get();

        foreach ($others as $other) {
            if (self::featuredWindowsOverlap(
                $event->featured_from,
                $event->featured_until,
                $other->featured_from,
                $other->featured_until,
            )) {
                throw ValidationException::withMessages([
                    'est_a_la_une' => 'Les dates de mise en avant chevauchent un autre événement déjà en avant.',
                ]);
            }
        }
    }

    /**
     * Indique si deux fenêtres de mise en avant se chevauchent (bornes ouvertes si null).
     */
    public static function featuredWindowsOverlap(
        ?CarbonInterface $fromA,
        ?CarbonInterface $untilA,
        ?CarbonInterface $fromB,
        ?CarbonInterface $untilB,
    ): bool {
        $startA = $fromA ?? now()->copy()->subYears(100);
        $endA = $untilA ?? now()->copy()->addYears(100);
        $startB = $fromB ?? now()->copy()->subYears(100);
        $endB = $untilB ?? now()->copy()->addYears(100);

        return $startA->lt($endB) && $startB->lt($endA);
    }
}
