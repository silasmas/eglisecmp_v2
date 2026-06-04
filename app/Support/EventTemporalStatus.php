<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Statut temporel d'un événement (passé, en cours, à venir) pour l'affichage public.
 */
final class EventTemporalStatus
{
    /**
     * @return array{temporalStatus: string, temporalLabel: string, dateEnd: string}
     */
    public static function resolve(Event $event): array
    {
        $start = $event->date_debut instanceof Carbon ? $event->date_debut : null;
        $end = $event->date_fin instanceof Carbon ? $event->date_fin : null;
        $now = now();

        if ($start !== null && $end !== null && $start->lte($now) && $end->gte($now)) {
            return [
                'temporalStatus' => 'ongoing',
                'temporalLabel' => 'En cours',
                'dateEnd' => $end->format('Y-m-d'),
            ];
        }

        if ($end !== null && $end->lt($now)) {
            return [
                'temporalStatus' => 'past',
                'temporalLabel' => $end->locale('fr')->diffForHumans(now(), ['parts' => 1]),
                'dateEnd' => $end->format('Y-m-d'),
            ];
        }

        if ($start !== null && $start->gt($now)) {
            return [
                'temporalStatus' => 'upcoming',
                'temporalLabel' => $start->locale('fr')->diffForHumans(now(), ['parts' => 1]),
                'dateEnd' => $end?->format('Y-m-d') ?? '',
            ];
        }

        if ($start !== null && $start->lt($now)) {
            return [
                'temporalStatus' => 'past',
                'temporalLabel' => $start->locale('fr')->diffForHumans(now(), ['parts' => 1]),
                'dateEnd' => $end?->format('Y-m-d') ?? $start->format('Y-m-d'),
            ];
        }

        return [
            'temporalStatus' => 'past',
            'temporalLabel' => 'Passé',
            'dateEnd' => $end?->format('Y-m-d') ?? '',
        ];
    }
}
