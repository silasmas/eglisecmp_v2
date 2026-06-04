<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\Mail\EventAlertMail;
use App\Models\AlertSubscription;
use App\Models\Event;
use App\Services\SmsSender;
use App\Support\SitePublicSerializer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie les abonnés opt-in lorsqu’un événement démarre, est mis en avant ou approche.
 */
final class EventAlertNotificationService
{
    private const CACHE_PREFIX = 'event.alert.notify.';

    public function __construct(
        private readonly SmsSender $sms,
    ) {}

    /**
     * @return array{ongoing: int, spotlight: int, upcoming: int}
     */
    public function checkAndNotify(): array
    {
        if (! (bool) config('site_public.youtube_live_notify.enabled', false)) {
            return ['ongoing' => 0, 'spotlight' => 0, 'upcoming' => 0];
        }

        $counts = [
            'ongoing' => $this->notifyOngoingEvents(),
            'spotlight' => $this->notifySpotlightEvents(),
            'upcoming' => $this->notifyUpcomingReminders(),
        ];

        return $counts;
    }

    private function notifyOngoingEvents(): int
    {
        $now = now();
        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('date_debut')
            ->whereNotNull('date_fin')
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->get();

        $sent = 0;
        foreach ($events as $event) {
            $cacheKey = self::CACHE_PREFIX.'ongoing.'.$event->getKey();
            if (Cache::has($cacheKey)) {
                continue;
            }
            $sent += $this->notifySubscribers($event, 'ongoing');
            Cache::put($cacheKey, true, $event->date_fin ?? now()->addDay());
        }

        return $sent;
    }

    private function notifySpotlightEvents(): int
    {
        $events = Event::query()->featuredSpotlightNow()->get();
        $sent = 0;

        foreach ($events as $event) {
            $cacheKey = self::CACHE_PREFIX.'spotlight.'.$event->getKey();
            if (Cache::has($cacheKey)) {
                continue;
            }
            $sent += $this->notifySubscribers($event, 'spotlight');
            $ttl = $event->featured_until ?? now()->addMonth();
            Cache::put($cacheKey, true, $ttl);
        }

        return $sent;
    }

    private function notifyUpcomingReminders(): int
    {
        $windowStart = now();
        $windowEnd = now()->addHours(24);

        $events = Event::query()
            ->where('is_active', true)
            ->whereNotNull('date_debut')
            ->where('date_debut', '>', $windowStart)
            ->where('date_debut', '<=', $windowEnd)
            ->get();

        $sent = 0;
        foreach ($events as $event) {
            $cacheKey = self::CACHE_PREFIX.'upcoming.'.$event->getKey();
            if (Cache::has($cacheKey)) {
                continue;
            }
            $sent += $this->notifySubscribers($event, 'upcoming');
            Cache::put($cacheKey, true, $event->date_debut ?? now()->addDay());
        }

        return $sent;
    }

    private function notifySubscribers(Event $event, string $alertType): int
    {
        $subscribers = AlertSubscription::query()->forEventAlerts()->get();
        if ($subscribers->isEmpty()) {
            return 0;
        }

        $locale = (string) config('site_public.youtube_sync.default_locale', 'fr');
        $public = SitePublicSerializer::eventToPublicArray($event, $locale, SitePublicSerializer::fallbackLocale());
        $title = (string) ($public['title'] ?? 'Événement CMP');
        $siteUrl = rtrim((string) config('site_public.youtube_live_notify.site_url', url('/')), '/');
        $eventsUrl = $siteUrl.'/events';

        $smsBase = match ($alertType) {
            'ongoing' => 'EVENT CMP (en cours)',
            'spotlight' => 'EVENT CMP (a la une)',
            default => 'EVENT CMP (bientot)',
        };
        $smsMessage = $this->sms->fitSingleSms($smsBase.' : '.$title.' — '.$eventsUrl);

        $count = 0;
        foreach ($subscribers as $subscription) {
            if (filled($subscription->email)) {
                try {
                    Mail::to($subscription->email)->queue(
                        new EventAlertMail($event, $alertType, $subscription, $locale)
                    );
                    $count++;
                } catch (\Throwable $exception) {
                    Log::warning('[event-alert] Email échoué', [
                        'event_id' => $event->getKey(),
                        'email' => $subscription->email,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            if (filled($subscription->phone)) {
                $result = $this->sms->send($subscription->phone, $smsMessage);
                if ($result->success) {
                    $count++;
                }
            }
        }

        Log::info('[event-alert] Notifications envoyées', [
            'event_id' => $event->getKey(),
            'type' => $alertType,
            'attempts' => $count,
        ]);

        return $count;
    }
}
