<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChurchWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe l'ouvrier que son dossier a été validé.
 */
class ChurchWorkerApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ChurchWorker $worker,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $badgeUrl = url('/ouvriers/badge/'.$this->worker->badge_token);
        $department = $this->worker->department?->name ?? 'votre département';

        return (new MailMessage)
            ->subject('CMP — Inscription ouvrier validée')
            ->greeting('Bonjour '.$this->worker->first_name.' !')
            ->line('Bonne nouvelle : votre dossier ouvrier a été validé par le responsable du département « '.$department.' ».')
            ->line('Vous êtes désormais enregistré(e) dans le système CMP avec le rôle Ouvrier.')
            ->line('Votre badge de service est accessible via le bouton ci-dessous (également imprimable).')
            ->action('Voir mon badge ouvrier', $badgeUrl)
            ->line('Que le Seigneur vous fortifie dans votre engagement au Centre Missionnaire Philadelphie.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'church_worker_approved',
            'worker_id' => $this->worker->id,
            'message' => 'Inscription ouvrier validée',
        ];
    }
}
