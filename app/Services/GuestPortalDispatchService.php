<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\GuestPortalLinkMail;
use App\Models\GuestInfoSubmission;
use App\Models\GuestPastor;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie le lien du portail invité (après soumission) par e-mail / SMS / WhatsApp.
 */
final class GuestPortalDispatchService
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Envoie le lien portail sur les canaux demandés.
     *
     * @param  list<string>  $channels
     * @return array{sent: int, failed: int, skipped: int, messages: list<string>, whatsapp_links: list<array{name: string, url: string}>}
     */
    public function dispatch(GuestInfoSubmission $submission, array $channels, ?User $actor = null): array
    {
        $submission->loadMissing('guestPastor.project');
        $pastor = $submission->guestPastor;
        if (! $pastor instanceof GuestPastor) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'messages' => ['Pasteur introuvable'], 'whatsapp_links' => []];
        }

        $project = $pastor->project;
        if ($project !== null && ! $project->portalIsOpen()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'messages' => ['Le portail a expiré (fin du projet).'], 'whatsapp_links' => []];
        }

        $channels = array_values(array_intersect($channels, [
            self::CHANNEL_EMAIL,
            self::CHANNEL_SMS,
            self::CHANNEL_WHATSAPP,
        ]));
        if ($channels === []) {
            $channels = [self::CHANNEL_EMAIL];
        }

        $portalUrl = $submission->shortPortalUrl();
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $messages = [];
        $whatsappLinks = [];

        foreach ($channels as $channel) {
            if ($channel === self::CHANNEL_EMAIL) {
                $r = $this->sendEmail($pastor, $portalUrl, $project?->title ?? 'CMP');
                $messages[] = 'E-mail : '.$r['status'].(isset($r['error']) ? ' — '.$r['error'] : '');
                if ($r['ok']) {
                    $sent++;
                } elseif (($r['status'] ?? '') === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                }
            }

            if ($channel === self::CHANNEL_SMS) {
                $r = $this->sendSms($pastor, $portalUrl);
                $messages[] = 'SMS : '.$r['status'].(isset($r['error']) ? ' — '.$r['error'] : '');
                if ($r['ok']) {
                    $sent++;
                } elseif (($r['status'] ?? '') === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                }
            }

            if ($channel === self::CHANNEL_WHATSAPP) {
                $digits = preg_replace('/\D+/', '', (string) ($pastor->phone ?? '')) ?? '';
                if ($digits === '') {
                    $skipped++;
                    $messages[] = 'WhatsApp : skipped — téléphone manquant';

                    continue;
                }
                if (str_starts_with($digits, '0') && strlen($digits) === 10) {
                    $digits = '243'.substr($digits, 1);
                }
                $text = 'CMP Philadelphie — votre portail d’accueil : '.$portalUrl;
                $url = 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
                $whatsappLinks[] = ['name' => $pastor->full_name, 'url' => $url];
                $sent++;
                $messages[] = 'WhatsApp : lien prêt';
            }
        }

        $submission->forceFill(['portal_link_sent_at' => now()])->save();

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'messages' => $messages,
            'whatsapp_links' => $whatsappLinks,
        ];
    }

    /**
     * @return array{ok: bool, status: string, error?: string}
     */
    private function sendEmail(GuestPastor $pastor, string $portalUrl, string $projectTitle): array
    {
        if (! filled($pastor->email) || ! filter_var((string) $pastor->email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'status' => 'skipped', 'error' => 'e-mail manquant'];
        }

        try {
            Mail::to($pastor->email)->send(new GuestPortalLinkMail($pastor, $portalUrl, $projectTitle));

            return ['ok' => true, 'status' => 'sent'];
        } catch (Throwable $e) {
            Log::warning('Échec e-mail lien portail invité', [
                'pastor_id' => $pastor->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, status: string, error?: string}
     */
    private function sendSms(GuestPastor $pastor, string $portalUrl): array
    {
        if (! filled($pastor->phone)) {
            return ['ok' => false, 'status' => 'skipped', 'error' => 'téléphone manquant'];
        }

        $body = $this->smsSender->fitSingleSms(
            'CMP Philadelphie: votre portail accueil (tenues, equipe, jours). Lien: '.$portalUrl
        );
        $result = $this->smsSender->send((string) $pastor->phone, $body, fitToSingle: true);

        return $result->success
            ? ['ok' => true, 'status' => 'sent']
            : ['ok' => false, 'status' => 'failed', 'error' => $result->error ?? 'SMS échoué'];
    }
}
