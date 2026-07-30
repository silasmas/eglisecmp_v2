<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChildPresentation;
use App\Support\SmsSendResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Valide une présentation d'enfant et notifie le parent par SMS.
 */
final class ChildPresentationConfirmationService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Indique si la demande peut encore être confirmée.
     */
    public function canConfirm(ChildPresentation $presentation): bool
    {
        return $presentation->canBeConfirmed();
    }

    /**
     * Raison affichée lorsque la confirmation est impossible.
     */
    public function blockReason(ChildPresentation $presentation): ?string
    {
        if ($presentation->status === ChildPresentation::STATUS_DECLINED) {
            return 'Cette présentation a été refusée.';
        }

        if ($presentation->status === ChildPresentation::STATUS_CONFIRMED
            && $presentation->isParentNotifiedBySms()) {
            return 'Le parent a déjà été informé par SMS.';
        }

        if ($presentation->presentation_date === null) {
            return 'Aucune date de présentation enregistrée.';
        }

        if ($presentation->presentation_date->copy()->startOfDay()->isPast()
            && ! $presentation->presentation_date->isToday()) {
            return 'La date de présentation est passée : confirmation impossible.';
        }

        return null;
    }

    /**
     * Confirme la présentation et envoie le SMS au parent.
     *
     * @return array{confirmed: bool, sms: SmsSendResult}
     */
    public function confirm(ChildPresentation $presentation): array
    {
        if (! $this->canConfirm($presentation)) {
            throw ValidationException::withMessages([
                'status' => $this->blockReason($presentation) ?? 'Confirmation impossible.',
            ]);
        }

        $smsResult = $this->sendConfirmationSms($presentation);
        $this->persistSmsResult($presentation, $smsResult);

        if ($smsResult->isNotified()) {
            $presentation->status = ChildPresentation::STATUS_CONFIRMED;
            $presentation->confirmed_at = now();
            $presentation->confirmed_by = Auth::id();
        } else {
            $presentation->status = ChildPresentation::STATUS_PENDING;
        }

        $presentation->save();

        return [
            'confirmed' => $smsResult->isNotified(),
            'sms' => $smsResult,
        ];
    }

    /**
     * Refuse une demande en attente.
     */
    public function decline(ChildPresentation $presentation): void
    {
        if ($presentation->status === ChildPresentation::STATUS_CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => 'Une présentation déjà confirmée ne peut pas être refusée.',
            ]);
        }

        $presentation->status = ChildPresentation::STATUS_DECLINED;
        $presentation->save();
    }

    /**
     * Compose et envoie le SMS de confirmation.
     */
    private function sendConfirmationSms(ChildPresentation $presentation): SmsSendResult
    {
        $phone = trim($presentation->phone);

        if ($phone === '') {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_NO_PHONE,
                success: false,
                error: 'Numero de telephone absent.',
            );
        }

        $presentation->loadMissing('children');
        $message = $this->smsSender->fitSingleSms($this->buildConfirmationMessage($presentation));

        return $this->smsSender->send($phone, $message);
    }

    /**
     * Enregistre le retour SMS sur la demande.
     */
    private function persistSmsResult(ChildPresentation $presentation, SmsSendResult $result): void
    {
        $presentation->confirmation_sms_status = match ($result->status) {
            SmsSendResult::STATUS_SENT => ChildPresentation::SMS_STATUS_SENT,
            SmsSendResult::STATUS_SIMULATED => ChildPresentation::SMS_STATUS_SIMULATED,
            SmsSendResult::STATUS_NO_PHONE => ChildPresentation::SMS_STATUS_NO_PHONE,
            default => ChildPresentation::SMS_STATUS_FAILED,
        };

        $presentation->confirmation_sms_sent_at = $result->isNotified() ? now() : null;

        $responseParts = array_filter([
            $result->response,
            $result->error,
        ]);

        $presentation->confirmation_sms_response = $responseParts !== []
            ? implode(' | ', $responseParts)
            : null;
    }

    /**
     * Texte SMS : confirmation + présence au début du culte.
     */
    private function buildConfirmationMessage(ChildPresentation $presentation): string
    {
        $dateLabel = $presentation->presentation_date?->format('d/m/Y') ?? 'date prevue';
        $firstParent = trim(explode(' ', trim($presentation->parent_names))[0] ?? $presentation->parent_names);
        $firstParent = $this->shorten($firstParent, 18);

        $childNames = $presentation->children
            ->pluck('full_name')
            ->map(fn (string $name): string => $this->shorten(trim(explode(' ', $name)[0] ?? $name), 12))
            ->filter()
            ->values()
            ->all();

        $childrenLabel = $childNames !== []
            ? implode(', ', array_slice($childNames, 0, 2))
            : 'votre enfant';

        return "{$firstParent}, presentation de {$childrenLabel} confirmee le {$dateLabel}. Soyez la au debut du culte. Eglise CMP";
    }

    /**
     * Tronque une chaîne sans dépasser la longueur donnée.
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
