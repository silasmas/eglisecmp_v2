<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\AlertSubscription;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Courriel d’alerte événement (début, mise en avant ou rappel).
 */
class EventAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  'ongoing'|'spotlight'|'upcoming'  $alertType
     */
    public function __construct(
        public Event $event,
        public string $alertType,
        public AlertSubscription $subscription,
        public string $locale = 'fr',
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->eventTitle();

        return new Envelope(
            subject: match ($this->alertType) {
                'ongoing' => 'Événement en cours : '.$title,
                'spotlight' => 'À ne pas manquer : '.$title,
                default => 'Rappel événement : '.$title,
            },
        );
    }

    public function content(): Content
    {
        $siteUrl = rtrim((string) config('site_public.youtube_live_notify.site_url', url('/')), '/');
        $unsubscribeUrl = $siteUrl.'/alertes/desabonnement?token='.$this->subscription->unsubscribe_token;

        return new Content(
            view: 'mail.event-alert',
            with: [
                'event' => $this->event,
                'eventTitle' => $this->eventTitle(),
                'alertType' => $this->alertType,
                'alertLabel' => $this->alertLabel(),
                'eventDate' => $this->formatEventDate(),
                'eventTime' => $this->formatEventTime(),
                'location' => $this->eventLocation(),
                'eventsUrl' => $siteUrl.'/events',
                'unsubscribeUrl' => $unsubscribeUrl,
                'logoUrl' => config('site_public.mail_logo_url') ?: asset('images/logo-cmp.png'),
            ],
        );
    }

    private function eventTitle(): string
    {
        $designation = $this->event->designation;
        if (! is_array($designation)) {
            return 'Événement CMP';
        }

        $title = (string) ($designation[$this->locale] ?? reset($designation) ?: '');

        return $title !== '' ? $title : 'Événement CMP';
    }

    private function eventLocation(): string
    {
        $lieu = $this->event->getAttribute('lieu');
        if (is_string($lieu) && trim($lieu) !== '') {
            return trim($lieu);
        }

        return '—';
    }

    private function alertLabel(): string
    {
        return match ($this->alertType) {
            'ongoing' => 'L’événement a commencé',
            'spotlight' => 'Événement mis en avant',
            default => 'Rappel — événement à venir',
        };
    }

    private function formatEventDate(): string
    {
        $start = $this->event->date_debut;
        if (! $start instanceof Carbon) {
            return '';
        }

        return $start->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    private function formatEventTime(): string
    {
        $start = $this->event->date_debut;
        $end = $this->event->date_fin;
        if (! $start instanceof Carbon) {
            return '';
        }
        $startStr = $start->format('H:i');
        if ($end instanceof Carbon) {
            return $startStr.' – '.$end->format('H:i');
        }

        return $startStr;
    }
}
