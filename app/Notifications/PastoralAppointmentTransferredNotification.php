<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SiteInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le pasteur destinataire qu’un fidèle lui a été redirigé / orienté.
 */
class PastoralAppointmentTransferredNotification extends Notification
{
    use Queueable;

    /**
     * @param  SiteInquiry  $inquiry  Dossier RDV transféré.
     * @param  string  $transferType  Libellé (ex. « Redirection admin », « Orientation »).
     */
    public function __construct(
        private readonly SiteInquiry $inquiry,
        private readonly string $transferType = 'Transfert',
    ) {}

    /**
     * Canaux de notification (base + e-mail si disponible).
     *
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
     * Message e-mail pour le pasteur destinataire.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->inquiry->preferred_at?->timezone(config('app.timezone'))->format('d/m/Y à H:i') ?? '—';

        return (new MailMessage)
            ->subject($this->transferType.' — rendez-vous pastoral CMP')
            ->greeting('Bonjour,')
            ->line('Un fidèle vous a été confié ('.$this->transferType.').')
            ->line('Fidèle : '.$this->inquiry->name)
            ->line('Téléphone : '.($this->inquiry->phone ?? '—'))
            ->line('Créneau : '.$when)
            ->line('Merci d’accuser réception dans le module Réception pastorale.')
            ->action('Ouvrir le dossier', url('/admin/pastoral-reception/'.$this->inquiry->id))
            ->salutation('Centre Missionnaire Philadelphie');
    }

    /**
     * Payload notification Filament / database.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $when = $this->inquiry->preferred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—';

        return [
            'title' => $this->transferType.' — fidèle à recevoir',
            'body' => sprintf('%s — %s', $this->inquiry->name, $when),
            'inquiry_id' => $this->inquiry->id,
        ];
    }
}
