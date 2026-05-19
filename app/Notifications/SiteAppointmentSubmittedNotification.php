<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Minister;
use App\Models\SiteInquiry;
use App\Support\SitePublicSerializer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie les administrateurs (base + courriel) d’une demande de rendez-vous en attente.
 */
class SiteAppointmentSubmittedNotification extends Notification
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

        if (
            filled($notifiable->email ?? null)
            && (bool) ($notifiable->notifiable ?? false)
        ) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return MailMessage Message de confirmation à traiter côté admin.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->inquiry->preferred_at?->timezone(config('app.timezone'))->format('d/m/Y à H:i') ?? '—';
        $ministerName = $this->ministerLabel();

        return (new MailMessage)
            ->subject('Nouvelle demande de rendez-vous — CMP')
            ->greeting('Bonjour,')
            ->line('Une nouvelle demande de rendez-vous pastoral attend votre confirmation.')
            ->line("Visiteur : {$this->inquiry->name}")
            ->line("Téléphone : ".($this->inquiry->phone ?? '—'))
            ->line("Pasteur : {$ministerName}")
            ->line("Créneau souhaité : {$when}")
            ->line('Motif :')
            ->line($this->inquiry->message)
            ->action('Voir dans l’administration', url('/admin'))
            ->salutation('Centre Missionnaire Philadelphie');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $when = $this->inquiry->preferred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—';

        return [
            'title' => 'Rendez-vous à confirmer',
            'body' => sprintf(
                '%s — %s avec %s',
                $this->inquiry->name,
                $when,
                $this->ministerLabel(),
            ),
            'inquiry_id' => $this->inquiry->id,
        ];
    }

    /**
     * Libellé du pasteur associé à la demande.
     */
    private function ministerLabel(): string
    {
        if ($this->inquiry->minister_id === null) {
            return 'Non précisé';
        }

        $minister = Minister::query()->find($this->inquiry->minister_id);
        if ($minister === null) {
            return 'Pasteur #'.$this->inquiry->minister_id;
        }

        $locale = (string) config('app.locale', 'fr');
        $fallback = SitePublicSerializer::fallbackLocale();
        $name = SitePublicSerializer::text($minister->fullname, $locale, $fallback);

        return $name !== '' ? $name : 'Pasteur';
    }
}
