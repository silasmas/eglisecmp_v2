<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChurchWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoie à l’ouvrier le lien public pour compléter / modifier son dossier.
 */
class ChurchWorkerEditLinkNotification extends Notification
{
    use Queueable;

    /**
     * @param  ChurchWorker  $worker  Dossier concerné
     * @param  string  $editUrl  URL publique du formulaire
     */
    public function __construct(
        private readonly ChurchWorker $worker,
        private readonly string $editUrl,
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
        $department = $this->worker->department?->name ?? 'votre département';
        $expires = $this->worker->edit_token_expires_at?->format('d/m/Y') ?? '14 jours';

        return (new MailMessage)
            ->subject('CMP — Complétez / mettez à jour votre dossier ouvrier')
            ->greeting('Bonjour '.$this->worker->first_name.' !')
            ->line('Le Centre Missionnaire Philadelphie vous invite à compléter ou mettre à jour votre dossier ouvrier pour le département « '.$department.' ».')
            ->line('Ce lien est personnel et valable jusqu’au '.$expires.'. Une vérification par code e-mail (OTP) sera demandée.')
            ->action('Ouvrir mon dossier ouvrier', $this->editUrl)
            ->line('Si vous n’êtes pas concerné(e), ignorez simplement ce message.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'church_worker_edit_link',
            'worker_id' => $this->worker->id,
            'edit_url' => $this->editUrl,
        ];
    }
}
