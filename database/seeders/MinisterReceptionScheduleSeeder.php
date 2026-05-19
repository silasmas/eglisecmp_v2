<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Minister;
use App\Models\MinisterReceptionSchedule;
use Illuminate\Database\Seeder;

/**
 * Renseigne des horaires de réception pour les pasteurs actifs (démo rendez-vous publics).
 */
class MinisterReceptionScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $ministers = Minister::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(6)
            ->get();

        if ($ministers->isEmpty()) {
            $this->command?->warn('Aucun pasteur actif : horaires de réception non créés.');

            return;
        }

        $weekdaySlots = [
            ['day' => 1, 'start' => '09:00', 'end' => '12:00'],
            ['day' => 1, 'start' => '14:00', 'end' => '17:00'],
            ['day' => 2, 'start' => '10:00', 'end' => '13:00'],
            ['day' => 3, 'start' => '09:00', 'end' => '12:00'],
            ['day' => 4, 'start' => '14:00', 'end' => '18:00'],
            ['day' => 5, 'start' => '09:00', 'end' => '11:30'],
            ['day' => 6, 'start' => '10:00', 'end' => '12:00'],
        ];

        foreach ($ministers as $index => $minister) {
            $slots = $index === 0 ? $weekdaySlots : array_slice($weekdaySlots, 0, 4 + ($index % 3));

            foreach ($slots as $slot) {
                MinisterReceptionSchedule::query()->firstOrCreate(
                    [
                        'minister_id' => $minister->id,
                        'day_of_week' => $slot['day'],
                        'starts_at' => $slot['start'],
                    ],
                    [
                        'ends_at' => $slot['end'],
                        'slot_minutes' => 30,
                        'is_active' => true,
                    ],
                );
            }
        }

        $this->command?->info('Horaires de réception créés pour '.$ministers->count().' pasteur(s).');
    }
}
