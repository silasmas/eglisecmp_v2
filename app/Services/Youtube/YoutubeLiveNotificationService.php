<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Mail\YoutubeLiveStartedMail;
use App\Models\AlertSubscription;
use App\Services\SmsSender;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Détecte le démarrage d’un live YouTube et notifie les abonnés opt-in (email + SMS).
 */
final class YoutubeLiveNotificationService
{
    private const PREVIOUS_STATE_KEY = 'youtube.live.notify.previous';

    private const NOTIFIED_PREFIX = 'youtube.live.notify.sent.';

    public function __construct(
        private readonly YoutubeLiveStatusService $liveStatus,
        private readonly SmsSender $sms,
    ) {}

    /**
     * Vérifie l’API et envoie les notifications si un nouveau live vient de démarrer.
     *
     * @return array{notified: bool, videoId: string|null, emails: int, sms: int}
     */
    public function checkAndNotify(): array
    {
        if (! (bool) config('site_public.youtube_live_notify.enabled', false)) {
            return ['notified' => false, 'videoId' => null, 'emails' => 0, 'sms' => 0];
        }

        // Utilise le cache déjà rafraîchi par youtube:check-live (évite un 2e appel API).
        $snapshot = $this->liveStatus->snapshot(false);
        $live = $snapshot['live'];
        $isLive = $snapshot['isLive'] && $live !== null;

        /** @var array{isLive: bool, videoId: string}|null $previous */
        $previous = Cache::get(self::PREVIOUS_STATE_KEY);
        $prevLive = is_array($previous) && ($previous['isLive'] ?? false) === true;
        $prevVideoId = is_array($previous) ? (string) ($previous['videoId'] ?? '') : '';

        $videoId = $isLive ? (string) ($live['videoId'] ?? '') : '';

        Cache::put(self::PREVIOUS_STATE_KEY, [
            'isLive' => $isLive,
            'videoId' => $videoId,
        ], now()->addDays(2));

        if (! $isLive || $videoId === '') {
            return ['notified' => false, 'videoId' => null, 'emails' => 0, 'sms' => 0];
        }

        $isNewLive = ! $prevLive || $prevVideoId !== $videoId;
        if (! $isNewLive) {
            return ['notified' => false, 'videoId' => $videoId, 'emails' => 0, 'sms' => 0];
        }

        if (Cache::has(self::NOTIFIED_PREFIX.$videoId)) {
            return ['notified' => false, 'videoId' => $videoId, 'emails' => 0, 'sms' => 0];
        }

        $emailsSent = $this->sendEmails($live);
        $smsSent = $this->sendSms($live);

        Cache::put(self::NOTIFIED_PREFIX.$videoId, true, now()->addDays(14));

        Log::info('[youtube-live-notify] Live notifié', [
            'videoId' => $videoId,
            'emails' => $emailsSent,
            'sms' => $smsSent,
        ]);

        return [
            'notified' => true,
            'videoId' => $videoId,
            'emails' => $emailsSent,
            'sms' => $smsSent,
        ];
    }

    /**
     * @param  array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}  $live
     */
    private function sendEmails(array $live): int
    {
        $count = 0;
        $subscribers = AlertSubscription::query()->forLiveAlerts()->get();

        foreach ($subscribers as $subscription) {
            if (! filled($subscription->email)) {
                continue;
            }
            try {
                Mail::to($subscription->email)->queue(
                    new YoutubeLiveStartedMail($live, $subscription->unsubscribe_token)
                );
                $count++;
            } catch (\Throwable $exception) {
                Log::warning('[youtube-live-notify] Email échoué', [
                    'email' => $subscription->email,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * @param  array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}  $live
     */
    private function sendSms(array $live): int
    {
        $siteUrl = rtrim((string) config('site_public.youtube_live_notify.site_url', url('/')), '/');
        $title = Str::limit((string) ($live['title'] ?? 'Live CMP'), 40);
        $watchUrl = (string) ($live['watchUrl'] ?? $siteUrl);
        $message = $this->sms->fitSingleSms('LIVE CMP : '.$title.' — '.$watchUrl);

        $count = 0;
        $subscribers = AlertSubscription::query()->forLiveAlerts()->get();

        foreach ($subscribers as $subscription) {
            if (! filled($subscription->phone)) {
                continue;
            }
            $result = $this->sms->send($subscription->phone, $message);
            if ($result->success) {
                $count++;
            }
        }

        return $count;
    }
}
