<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SiteInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return MailMessage Message transmis à l’équipe d’intercession.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $displayName = $this->inquiry->is_anonymous
            ? 'Anonyme (demande confidentielle)'
            : $this->inquiry->name;

        $mail = (new MailMessage)
            ->subject('Nouvelle requête de prière — CMP')
            ->greeting('Bonjour,')
            ->line('Une nouvelle requête de prière vient d’être déposée sur le site.')
            ->line("Nom : {$displayName}")
            ->line('Pays : '.($this->inquiry->country ?? '—'))
            ->line('Téléphone : '.($this->inquiry->phone ?? '—'));

        if (filled($this->inquiry->email)) {
            $mail->line("Courriel : {$this->inquiry->email}");
        }

        if ($this->inquiry->is_anonymous) {
            $mail->line('Cette demande a été envoyée dans l’anonymat.');
        }

        return $mail
            ->line('Requête :')
            ->line($this->inquiry->message)
            ->action('Voir dans l’administration', url('/admin/site-inquiries'))
            ->salutation('Centre Missionnaire Philadelphie');
    }

    /**
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
