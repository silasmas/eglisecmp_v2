<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Calcule les dimanches de présentation (2e et 4e du mois) et le message ECODIM.
 */
final class ChildPresentationAvailabilityService
{
    /**
     * Liste les prochaines dates de présentation (2e et 4e dimanches).
     *
     * @param  int|null  $limit  Nombre de dates à retourner (défaut config).
     * @return list<array{date: string, label: string}>
     */
    public function upcomingDates(?int $limit = null): array
    {
        $limit = $limit ?? max(1, (int) config('child_presentation.available_dates_count', 8));
        $timezone = (string) config('app.timezone', 'Africa/Kinshasa');
        $cursor = Carbon::now($timezone)->startOfDay();
        $dates = [];

        // Parcourt jusqu'à 18 mois pour constituer la liste.
        for ($monthOffset = 0; $monthOffset < 18 && count($dates) < $limit; $monthOffset++) {
            $month = $cursor->copy()->startOfMonth()->addMonths($monthOffset);

            foreach ($this->presentationSundaysInMonth($month) as $sunday) {
                if ($sunday->lt($cursor)) {
                    continue;
                }

                $dates[] = [
                    'date' => $sunday->toDateString(),
                    'label' => $sunday->locale('fr')->translatedFormat('l j F Y'),
                ];

                if (count($dates) >= $limit) {
                    break;
                }
            }
        }

        return $dates;
    }

    /**
     * Vérifie qu'une date est bien un 2e ou 4e dimanche futur.
     *
     * @param  string  $date  Date au format Y-m-d.
     */
    public function isValidPresentationDate(string $date): bool
    {
        $timezone = (string) config('app.timezone', 'Africa/Kinshasa');

        try {
            $carbon = Carbon::createFromFormat('Y-m-d', $date, $timezone)?->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        if (! $carbon instanceof Carbon) {
            return false;
        }

        if ($carbon->isPast() && ! $carbon->isToday()) {
            return false;
        }

        if (! $carbon->isSunday()) {
            return false;
        }

        $sundays = $this->presentationSundaysInMonth($carbon->copy()->startOfMonth());

        foreach ($sundays as $sunday) {
            if ($sunday->toDateString() === $carbon->toDateString()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Message indiquant le temps restant avant l'entrée à l'ECODIM.
     *
     * @param  int  $ageYears  Âge en années.
     * @param  int  $ageMonths  Mois complémentaires (0–11).
     * @return array{eligible: bool, message: string, months_remaining: int}
     */
    public function ecodimMessage(int $ageYears, int $ageMonths = 0): array
    {
        $entryYears = max(1, (int) config('child_presentation.ecodim_entry_age_years', 3));
        $entryMonths = $entryYears * 12;
        $ageInMonths = max(0, ($ageYears * 12) + max(0, min(11, $ageMonths)));
        $remaining = $entryMonths - $ageInMonths;

        if ($remaining <= 0) {
            return [
                'eligible' => true,
                'message' => "Votre enfant a l'âge pour rejoindre l'ECODIM.",
                'months_remaining' => 0,
            ];
        }

        $yearsLeft = intdiv($remaining, 12);
        $monthsLeft = $remaining % 12;
        $parts = [];

        if ($yearsLeft > 0) {
            $parts[] = $yearsLeft === 1 ? '1 an' : "{$yearsLeft} ans";
        }

        if ($monthsLeft > 0) {
            $parts[] = $monthsLeft === 1 ? '1 mois' : "{$monthsLeft} mois";
        }

        $duration = implode(' et ', $parts);

        return [
            'eligible' => false,
            'message' => "Il reste {$duration} avant que votre enfant puisse venir à l'ECODIM (à partir de {$entryYears} ans).",
            'months_remaining' => $remaining,
        ];
    }

    /**
     * Retourne le 2e et le 4e dimanche d'un mois donné.
     *
     * @return list<Carbon>
     */
    private function presentationSundaysInMonth(Carbon $monthStart): array
    {
        $first = $monthStart->copy()->startOfMonth();

        if (! $first->isSunday()) {
            $first->next(Carbon::SUNDAY);
        }

        $sundays = [];
        $current = $first->copy();
        $nth = 1;

        while ($current->month === $monthStart->month) {
            if ($nth === 2 || $nth === 4) {
                $sundays[] = $current->copy()->startOfDay();
            }

            $current->addWeek();
            $nth++;
        }

        return $sundays;
    }
}
