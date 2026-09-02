<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchWorker;
use App\Notifications\ChurchWorkerEditLinkNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Génère le lien de modification et le notifie (e-mail et/ou SMS), un ouvrier à la fois.
 */
final class ChurchWorkerEditLinkNotifyService
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Notifie un ouvrier (génère / régénère le jeton puis envoie les canaux choisis).
     *
     * @param  list<string>  $channels
     * @return array{ok: bool, worker_id: int, name: string, url: string|null, email: string|null, sms: string|null, errors: list<string>}
     */
    public function notifyOne(ChurchWorker $worker, array $channels, ?int $ttlDays = null): array
    {
        $channels = array_values(array_intersect($channels, [self::CHANNEL_EMAIL, self::CHANNEL_SMS]));
        if ($channels === []) {
            $channels = [self::CHANNEL_EMAIL];
        }

        $worker->issueEditToken($ttlDays);
        $url = $worker->profileEditUrl();

        $result = [
            'ok' => true,
            'worker_id' => $worker->id,
            'name' => $worker->fullName(),
            'url' => $url,
            'email' => null,
            'sms' => null,
            'errors' => [],
        ];

        if ($url === null || $url === '') {
            $result['ok'] = false;
            $result['errors'][] = 'URL de modification indisponible.';

            return $result;
        }

        foreach ($channels as $channel) {
            if ($channel === self::CHANNEL_EMAIL) {
                $emailStatus = $this->sendEmail($worker, $url);
                $result['email'] = $emailStatus['status'];
                if (! $emailStatus['ok']) {
                    $result['ok'] = false;
                    $result['errors'][] = $emailStatus['error'] ?? 'E-mail échoué';
                }
            }

            if ($channel === self::CHANNEL_SMS) {
                $smsStatus = $this->sendSms($worker, $url);
                $result['sms'] = $smsStatus['status'];
                if (! $smsStatus['ok']) {
                    $result['ok'] = false;
                    $result['errors'][] = $smsStatus['error'] ?? 'SMS échoué';
                }
            }
        }

        return $result;
    }

    /**
     * Notifie plusieurs ouvriers un par un (pas de rollback global).
     *
     * @param  Collection<int, ChurchWorker>|iterable<ChurchWorker>  $workers
     * @param  list<string>  $channels
     * @return array{sent: int, failed: int, results: list<array{ok: bool, worker_id: int, name: string, url: string|null, email: string|null, sms: string|null, errors: list<string>}>}
     */
    public function notifyMany(iterable $workers, array $channels, ?int $ttlDays = null): array
    {
        $sent = 0;
        $failed = 0;
        $results = [];

        foreach ($workers as $worker) {
            if (! $worker instanceof ChurchWorker) {
                continue;
            }

            $row = $this->notifyOne($worker, $channels, $ttlDays);
            $results[] = $row;

            if ($row['ok']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * @return array{ok: bool, status: string, error: string|null}
     */
    private function sendEmail(ChurchWorker $worker, string $url): array
    {
        $email = trim((string) $worker->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'status' => 'skipped_no_email', 'error' => $worker->fullName().' : e-mail manquant ou invalide'];
        }

        try {
            Notification::route('mail', $email)
                ->notify(new ChurchWorkerEditLinkNotification($worker, $url));

            return ['ok' => true, 'status' => 'sent', 'error' => null];
        } catch (Throwable $e) {
            Log::warning('Échec e-mail lien édition ouvrier', [
                'worker_id' => $worker->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 'failed', 'error' => $worker->fullName().' : '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, status: string, error: string|null}
     */
    private function sendSms(ChurchWorker $worker, string $url): array
    {
        $phone = trim((string) $worker->phone);
        if ($phone === '') {
            return ['ok' => false, 'status' => 'skipped_no_phone', 'error' => $worker->fullName().' : téléphone manquant'];
        }

        $body = $this->smsSender->fitSingleSms(
            'CMP Philadelphie: completez votre dossier ouvrier. Lien: '.$url
        );

        $result = $this->smsSender->send($phone, $body, fitToSingle: true);

        if ($result->success) {
            return ['ok' => true, 'status' => 'sent', 'error' => null];
        }

        return [
            'ok' => false,
            'status' => $result->status,
            'error' => $worker->fullName().' : '.($result->error ?: 'SMS échoué'),
        ];
    }
}
