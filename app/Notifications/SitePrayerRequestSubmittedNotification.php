<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SiteInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifie l’équipe d’intercession (courriel + centre de notifications) d’une nouvelle requête de prière.
 */
class SitePrayerRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SiteInquiry $inquiry,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Notification Filament uniquement (le courriel est envoyé par {@see PrayerRequestNotificationService}).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $label = $this->inquiry->is_anonymous ? 'Anonyme' : $this->inquiry->name;

        return [
            'title' => 'Nouvelle requête de prière',
            'body' => sprintf('%s — %s', $label, mb_strimwidth($this->inquiry->message, 0, 120, '…')),
            'inquiry_id' => $this->inquiry->id,
        ];
    }
}
