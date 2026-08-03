<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MinisterReceptionSchedule;
use App\Models\SiteInquiry;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Démarre / termine les sessions de réception et calcule le respect du temps.
 */
final class PastoralSessionService
{
    public const DEFAULT_DURATION_MINUTES = 30;

    public const WARNING_REMAINING_SECONDS = 300;

    /**
     * Démarre le chrono à l’accusé de réception.
     */
    public function markReceived(SiteInquiry $inquiry): void
    {
        $duration = $this->resolveDurationMinutes($inquiry);

        $inquiry->update([
            'received_at' => now(),
            'session_started_at' => now(),
            'session_duration_minutes' => $duration,
            'reception_status' => SiteInquiry::RECEPTION_IN_PROGRESS,
            'dossier_status' => SiteInquiry::DOSSIER_OPEN,
            'suspended_at' => null,
            'time_respected' => null,
        ]);
    }

    /**
     * Suspend le dossier (pas de clôture définitive).
     */
    public function suspend(SiteInquiry $inquiry): void
    {
        $inquiry->update([
            'dossier_status' => SiteInquiry::DOSSIER_SUSPENDED,
            'suspended_at' => now(),
            'reception_status' => SiteInquiry::RECEPTION_IN_PROGRESS,
        ]);
    }

    /**
     * Clôture le dossier et note si le temps imparti a été respecté.
     */
    public function close(SiteInquiry $inquiry): void
    {
        $timeRespected = $this->computeTimeRespected($inquiry);

        $inquiry->update([
            'dossier_status' => SiteInquiry::DOSSIER_CLOSED,
            'reception_status' => SiteInquiry::RECEPTION_COMPLETED,
            'completed_at' => now(),
            'closed_at' => now(),
            'suspended_at' => null,
            'next_appointment_at' => null,
            'time_respected' => $timeRespected,
        ]);
    }

    /**
     * Planifie un prochain RDV : dossier non clos, couleur « suivi ».
     *
     * @param  Carbon|string  $nextAt  Prochain créneau.
     */
    public function scheduleNext(SiteInquiry $inquiry, Carbon|string $nextAt): void
    {
        $timeRespected = $this->computeTimeRespected($inquiry);
        $next = $nextAt instanceof Carbon ? $nextAt : Carbon::parse($nextAt);

        $inquiry->update([
            'dossier_status' => SiteInquiry::DOSSIER_FOLLOW_UP,
            'reception_status' => SiteInquiry::RECEPTION_AWAITING,
            'preferred_at' => $next,
            'next_appointment_at' => $next,
            'completed_at' => now(),
            'closed_at' => null,
            'suspended_at' => null,
            'received_at' => null,
            'session_started_at' => null,
            'session_duration_minutes' => null,
            'time_respected' => $timeRespected,
        ]);
    }

    /**
     * Réouverture réservée au pasteur titulaire (pour consulter / reprendre).
     */
    public function reopen(SiteInquiry $inquiry, User $user): void
    {
        $inquiry->update([
            'dossier_status' => SiteInquiry::DOSSIER_OPEN,
            'reception_status' => SiteInquiry::RECEPTION_IN_PROGRESS,
            'closed_at' => null,
            'suspended_at' => null,
            'reopened_by' => $user->id,
            'reopened_at' => now(),
        ]);
    }

    /**
     * Ajuste manuellement la durée de session (minutes).
     */
    public function updateDuration(SiteInquiry $inquiry, int $minutes): void
    {
        $inquiry->update([
            'session_duration_minutes' => max(5, min(240, $minutes)),
        ]);
    }

    /**
     * Indique si le temps est encore respecté / restant jusqu’à la fin.
     *
     * @return array{
     *     started: bool,
     *     duration_minutes: int,
     *     elapsed_seconds: int,
     *     remaining_seconds: int,
     *     warning: bool,
     *     overdue: bool,
     *     label: string
     * }
     */
    public function chronoState(SiteInquiry $inquiry): array
    {
        $duration = (int) ($inquiry->session_duration_minutes ?: self::DEFAULT_DURATION_MINUTES);
        $startedAt = $inquiry->session_started_at ?? $inquiry->received_at;

        if (! $startedAt instanceof Carbon) {
            return [
                'started' => false,
                'duration_minutes' => $duration,
                'elapsed_seconds' => 0,
                'remaining_seconds' => $duration * 60,
                'warning' => false,
                'overdue' => false,
                'label' => 'Chrono non démarré',
            ];
        }

        $elapsed = max(0, (int) $startedAt->diffInSeconds(now()));
        $total = $duration * 60;
        $remaining = $total - $elapsed;
        $overdue = $remaining < 0;
        $warning = ! $overdue && $remaining <= self::WARNING_REMAINING_SECONDS;

        return [
            'started' => true,
            'duration_minutes' => $duration,
            'elapsed_seconds' => $elapsed,
            'remaining_seconds' => $remaining,
            'warning' => $warning,
            'overdue' => $overdue,
            'label' => $overdue
                ? 'Temps dépassé de '.$this->formatSeconds(abs($remaining))
                : 'Reste '.$this->formatSeconds($remaining),
        ];
    }

    /**
     * Calcule si la durée impartie a été respectée à la fin de session.
     */
    public function computeTimeRespected(SiteInquiry $inquiry): ?bool
    {
        $startedAt = $inquiry->session_started_at ?? $inquiry->received_at;
        $duration = (int) ($inquiry->session_duration_minutes ?: 0);

        if (! $startedAt instanceof Carbon || $duration <= 0) {
            return null;
        }

        $elapsedMinutes = $startedAt->diffInMinutes(now());

        return $elapsedMinutes <= $duration;
    }

    /**
     * Durée cible selon le créneau du pasteur (slot_minutes), sinon défaut.
     */
    public function resolveDurationMinutes(SiteInquiry $inquiry): int
    {
        if ($inquiry->minister_id === null || ! $inquiry->preferred_at instanceof Carbon) {
            return self::DEFAULT_DURATION_MINUTES;
        }

        $day = (int) $inquiry->preferred_at->isoWeekday();
        $time = $inquiry->preferred_at->format('H:i:s');

        $schedule = MinisterReceptionSchedule::query()
            ->where('minister_id', $inquiry->minister_id)
            ->where('is_active', true)
            ->where('day_of_week', $day)
            ->where('starts_at', '<=', $time)
            ->where('ends_at', '>', $time)
            ->orderByDesc('slot_minutes')
            ->first();

        $minutes = (int) ($schedule?->slot_minutes ?: self::DEFAULT_DURATION_MINUTES);

        return max(5, min(240, $minutes));
    }

    /**
     * Formate une durée en mm:ss.
     */
    private function formatSeconds(int $seconds): string
    {
        $seconds = abs($seconds);
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d', $m, $s);
    }
}
