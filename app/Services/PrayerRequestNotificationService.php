<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PrayerRequestSubmittedMail;
use App\Models\Role as LegacyRole;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Notifications\SitePrayerRequestSubmittedNotification;
use App\Support\PrayerTeamNotificationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les notifications d’intercession pour une requête de prière enregistrée.
 */
class PrayerRequestNotificationService
{
    /**
     * Notifie l’équipe de prière et enregistre le statut sur la demande.
     *
     * @param  SiteInquiry  $inquiry  Requête de prière persistée.
     * @return PrayerTeamNotificationResult Résumé des envois.
     */
    public function notifyAndRecord(SiteInquiry $inquiry): PrayerTeamNotificationResult
    {
        if ($inquiry->kind !== SiteInquiry::KIND_PRAYER) {
            return new PrayerTeamNotificationResult;
        }

        $emails = $this->resolveRecipientEmails();

        if ($emails->isEmpty()) {
            $this->persistResult(
                $inquiry,
                SiteInquiry::PRAYER_NOTIFY_NO_RECIPIENT,
                'Aucun destinataire intercession (rôle ou PRAYER_TEAM_EMAILS).',
            );

            return new PrayerTeamNotificationResult(errors: [
                'Configurez un utilisateur avec le rôle intercession ou PRAYER_TEAM_EMAILS dans .env',
            ]);
        }

        $result = new PrayerTeamNotificationResult;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new PrayerRequestSubmittedMail($inquiry));
                $result->sent++;
            } catch (\Throwable $exception) {
                $result->failed++;
                $result->errors[] = "{$email} : ".$exception->getMessage();
                Log::error('Échec envoi courriel requête de prière.', [
                    'inquiry_id' => $inquiry->id,
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->notifyRecipientsInApp($inquiry, $result->hasSuccess());

        $status = match (true) {
            $result->sent > 0 && $result->failed === 0 => SiteInquiry::PRAYER_NOTIFY_SENT,
            $result->sent > 0 => SiteInquiry::PRAYER_NOTIFY_PARTIAL,
            default => SiteInquiry::PRAYER_NOTIFY_FAILED,
        };

        $this->persistResult($inquiry, $status, $result->adminSummary());

        return $result;
    }

    /**
     * @deprecated Utiliser {@see notifyAndRecord()}.
     */
    public function notify(SiteInquiry $inquiry): void
    {
        $this->notifyAndRecord($inquiry);
    }

    /**
     * @return Collection<int, string> Adresses e-mail uniques des destinataires.
     */
    private function resolveRecipientEmails(): Collection
    {
        $emails = collect();

        foreach ($this->resolveRecipients() as $user) {
            if (filled($user->email)) {
                $emails->push(strtolower(trim((string) $user->email)));
            }
        }

        $configured = config('site_public.prayer_team_emails', []);

        if (is_string($configured)) {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }

        foreach ($configured as $email) {
            if (is_string($email) && filled($email)) {
                $emails->push(strtolower(trim($email)));
            }
        }

        return $emails->unique()->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveRecipients(): Collection
    {
        $configuredRoles = config('site_public.prayer_notification_roles', ['intercession']);

        $legacyRoleIds = LegacyRole::query()
            ->whereIn('name', $configuredRoles)
            ->pluck('id')
            ->all();

        $spatieRoleNames = LegacyRole::query()
            ->whereIn('name', $configuredRoles)
            ->whereNotNull('name')
            ->pluck('name')
            ->all();

        if ($legacyRoleIds === [] && $spatieRoleNames === []) {
            return collect();
        }

        return User::query()
            ->where(function ($query) use ($legacyRoleIds, $spatieRoleNames): void {
                if ($legacyRoleIds !== []) {
                    $query->whereIn('role_id', $legacyRoleIds);
                }
                if ($spatieRoleNames !== []) {
                    $query->orWhereHas('roles', function ($roleQuery) use ($spatieRoleNames): void {
                        $roleQuery->whereIn('name', $spatieRoleNames);
                    });
                }
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Alimente le centre de notifications Filament (sans renvoyer le courriel).
     */
    private function notifyRecipientsInApp(SiteInquiry $inquiry, bool $onlyIfMailSent): void
    {
        if ($onlyIfMailSent === false) {
            return;
        }

        foreach ($this->resolveRecipients() as $user) {
            $user->notify(new SitePrayerRequestSubmittedNotification($inquiry));
        }
    }

    /**
     * Persiste le statut de notification sur la requête de prière.
     */
    private function persistResult(SiteInquiry $inquiry, string $status, string $response): void
    {
        $inquiry->prayer_team_notification_status = $status;
        $inquiry->prayer_team_notified_at = in_array($status, [
            SiteInquiry::PRAYER_NOTIFY_SENT,
            SiteInquiry::PRAYER_NOTIFY_PARTIAL,
        ], true) ? now() : $inquiry->prayer_team_notified_at;
        $inquiry->prayer_team_notification_response = $response;
        $inquiry->save();
    }
}
