<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Resources\MinisterResource;
use App\Models\SiteInquiry;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Confirme un rendez-vous pastoral et notifie le fidèle par SMS.
 */
final class AppointmentConfirmationService
{
    public function __construct(
        private readonly SmsSender $smsSender,
        private readonly AppointmentAvailabilityService $availability,
    ) {}

    /**
     * Indique si la demande peut encore être confirmée (en attente et date future).
     */
    public function canConfirm(SiteInquiry $inquiry): bool
    {
        if ($inquiry->kind !== SiteInquiry::KIND_APPOINTMENT) {
            return false;
        }

        if ($inquiry->appointment_status !== SiteInquiry::STATUS_PENDING) {
            return false;
        }

        if (! $inquiry->preferred_at instanceof Carbon) {
            return false;
        }

        return $inquiry->preferred_at->isFuture();
    }

    /**
     * Raison affichée lorsque la confirmation est impossible.
     */
    public function blockReason(SiteInquiry $inquiry): ?string
    {
        if ($inquiry->kind !== SiteInquiry::KIND_APPOINTMENT) {
            return null;
        }

        if ($inquiry->appointment_status === SiteInquiry::STATUS_CONFIRMED) {
            return 'Ce rendez-vous est déjà confirmé.';
        }

        if ($inquiry->appointment_status === SiteInquiry::STATUS_DECLINED) {
            return 'Ce rendez-vous a été refusé.';
        }

        if (! $inquiry->preferred_at instanceof Carbon) {
            return 'Aucune date de rendez-vous enregistrée.';
        }

        if ($inquiry->preferred_at->isPast()) {
            return 'La date du rendez-vous est passée : confirmation impossible.';
        }

        return null;
    }

    /**
     * Confirme le rendez-vous et envoie le SMS au fidèle.
     *
     * @return array{confirmed: bool, smsSent: bool}
     */
    public function confirm(SiteInquiry $inquiry): array
    {
        if (! $this->canConfirm($inquiry)) {
            throw ValidationException::withMessages([
                'appointment_status' => $this->blockReason($inquiry) ?? 'Confirmation impossible.',
            ]);
        }

        if ($inquiry->bureau_id === null && $inquiry->minister_id !== null && $inquiry->preferred_at instanceof Carbon) {
            $bureauId = $this->availability->resolveBureauForSlot(
                (int) $inquiry->minister_id,
                $inquiry->preferred_at,
            );

            if ($bureauId !== null) {
                $inquiry->bureau_id = $bureauId;
            }
        }

        $inquiry->appointment_status = SiteInquiry::STATUS_CONFIRMED;
        $inquiry->save();

        $smsSent = $this->sendConfirmationSms($inquiry);

        return [
            'confirmed' => true,
            'smsSent' => $smsSent,
        ];
    }

    /**
     * Compose et envoie le SMS de confirmation.
     */
    private function sendConfirmationSms(SiteInquiry $inquiry): bool
    {
        $phone = trim((string) ($inquiry->phone ?? ''));

        if ($phone === '') {
            return false;
        }

        $inquiry->loadMissing(['minister', 'bureau']);

        $message = $this->smsSender->fitSingleSms($this->buildConfirmationMessage($inquiry));

        return $this->smsSender->send($phone, $message);
    }

    /**
     * Texte SMS court (1 segment, max 160 caracteres sans accents).
     */
    private function buildConfirmationMessage(SiteInquiry $inquiry): string
    {
        $preferredAt = $inquiry->preferred_at instanceof Carbon
            ? $inquiry->preferred_at->copy()->timezone(config('app.timezone'))
            : null;

        $dateLabel = $preferredAt instanceof Carbon
            ? $preferredAt->format('d/m/Y')
            : 'date prevue';

        $timeLabel = $preferredAt instanceof Carbon
            ? $preferredAt->format('H').'h'.$preferredAt->format('i')
            : '';

        $bureauName = trim((string) ($inquiry->bureau?->name ?? ''));

        if ($bureauName === '') {
            $bureauName = 'reception';
        }

        $firstName = trim(explode(' ', trim($inquiry->name))[0] ?? $inquiry->name);
        $firstName = $this->shorten($firstName, 20);

        $ministerName = MinisterResource::normalizeLegacyValue($inquiry->minister?->fullname ?? '') ?? '';
        $ministerName = $this->shorten($ministerName, 24);

        $timePart = $timeLabel !== '' ? " a {$timeLabel}" : '';

        $message = "{$firstName}, RDV confirme le {$dateLabel}{$timePart}, bureau {$bureauName}. Eglise CMP";

        if ($ministerName !== '') {
            $withMinister = "{$message} Ptr {$ministerName}.";

            if (strlen($withMinister) <= (int) config('sms.max_length', 160)) {
                $message = $withMinister;
            }
        }

        return $message;
    }

    /**
     * Tronque une chaine sans couper un mot si possible.
     */
    private function shorten(string $value, int $maxLength): string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(substr($value, 0, $maxLength));
    }
}
