<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Minister;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Notifications\PastoralAppointmentTransferredNotification;
use Illuminate\Support\Facades\Log;

/**
 * Envoie mail (et SMS si possible) au pasteur destinataire d’un transfert RDV.
 */
final class PastoralTransferNotificationService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Notifie le pasteur lié au minister_id actuel du dossier.
     *
     * @param  SiteInquiry  $inquiry  Dossier déjà mis à jour (nouveau minister_id).
     * @param  string  $transferType  Libellé du transfert.
     */
    public function notifyDestinationMinister(SiteInquiry $inquiry, string $transferType): void
    {
        $minister = Minister::query()
            ->with('user')
            ->find($inquiry->minister_id);

        if ($minister === null) {
            return;
        }

        $user = $minister->user;
        if ($user instanceof User) {
            $user->notify(new PastoralAppointmentTransferredNotification($inquiry, $transferType));
        }

        $phone = $this->resolveMinisterPhone($minister);
        if ($phone === null) {
            Log::info('Pastoral transfer: pas de téléphone pour SMS', [
                'inquiry_id' => $inquiry->id,
                'minister_id' => $minister->id,
            ]);

            return;
        }

        $when = $inquiry->preferred_at?->timezone(config('app.timezone'))->format('d/m H:i') ?? '—';
        $message = $this->smsSender->fitSingleSms(
            "CMP: {$transferType}. Fidele {$inquiry->name} ({$when}). Accusez reception dans l'admin."
        );

        $this->smsSender->send($phone, $message);
    }

    /**
     * Résout un numéro utilisable depuis le contact pasteur (ignore les e-mails).
     */
    private function resolveMinisterPhone(Minister $minister): ?string
    {
        $contact = trim((string) ($minister->contact ?? ''));
        if ($contact === '' || str_contains($contact, '@')) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $contact) ?? '';

        return strlen($digits) >= 8 ? $contact : null;
    }
}
