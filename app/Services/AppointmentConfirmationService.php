<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Resources\MinisterResource;
use App\Models\SiteInquiry;
use App\Support\SmsSendResult;
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
     * Indique si la demande peut encore être confirmée (date future et SMS non envoyé).
     */
    public function canConfirm(SiteInquiry $inquiry): bool
    {
        return $inquiry->canBeConfirmed();
    }

    /**
     * Raison affichée lorsque la confirmation est impossible.
     */
    public function blockReason(SiteInquiry $inquiry): ?string
    {
        if ($inquiry->kind !== SiteInquiry::KIND_APPOINTMENT) {
            return null;
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

        if ($inquiry->isFaithfulNotifiedBySms()) {
            return 'Le fidèle a déjà été informé par SMS.';
        }

        return null;
    }

    /**
     * Confirme le rendez-vous et envoie le SMS au fidèle.
     *
     * @return array{confirmed: bool, sms: SmsSendResult}
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

        $smsResult = $this->sendConfirmationSms($inquiry);
        $this->persistSmsResult($inquiry, $smsResult);

        if ($smsResult->isNotified()) {
            $inquiry->appointment_status = SiteInquiry::STATUS_CONFIRMED;
        } else {
            $inquiry->appointment_status = SiteInquiry::STATUS_PENDING;
        }

        $inquiry->save();

        return [
            'confirmed' => $smsResult->isNotified(),
            'sms' => $smsResult,
        ];
    }

    /**
     * Compose et envoie le SMS de confirmation.
     */
    private function sendConfirmationSms(SiteInquiry $inquiry): SmsSendResult
    {
        $phone = trim((string) ($inquiry->phone ?? ''));

        if ($phone === '') {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_NO_PHONE,
                success: false,
                error: 'Numero de telephone absent.',
            );
        }

        $inquiry->loadMissing(['minister', 'bureau']);

        $message = $this->smsSender->fitSingleSms($this->buildConfirmationMessage($inquiry));

        return $this->smsSender->send($phone, $message);
    }

    /**
     * Enregistre le retour SMS sur la demande.
     */
    private function persistSmsResult(SiteInquiry $inquiry, SmsSendResult $result): void
    {
        $inquiry->confirmation_sms_status = match ($result->status) {
            SmsSendResult::STATUS_SENT => SiteInquiry::SMS_STATUS_SENT,
            SmsSendResult::STATUS_SIMULATED => SiteInquiry::SMS_STATUS_SIMULATED,
            SmsSendResult::STATUS_NO_PHONE => SiteInquiry::SMS_STATUS_NO_PHONE,
            default => SiteInquiry::SMS_STATUS_FAILED,
        };

        $inquiry->confirmation_sms_sent_at = $result->isNotified() ? now() : null;

        $responseParts = array_filter([
            $result->response,
            $result->error,
        ]);

        $inquiry->confirmation_sms_response = $responseParts !== []
            ? implode(' | ', $responseParts)
            : null;
    }

    /**
     * Texte SMS court (1 segment, max 160 caracteres sans accents).
     */
    private function buildConfirmationMessage(SiteInquiry $inquiry): string
    {
        $preferredAt = $inquiry->preferred_at instanceof Carbon
            ? $inquiry->preferred_at->copy()->timezone((string) config('app.timezone'))
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
            $withMinister = "{$message} Pst {$ministerName}.";

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
