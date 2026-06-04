<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Courriel envoyé lorsqu’un live YouTube de la chaîne CMP démarre.
 */
class YoutubeLiveStartedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{isLive: bool, videoId: string, title: string, embedUrl: string, thumbnailUrl: string, watchUrl: string}  $live
     */
    public function __construct(
        public array $live,
        public string $unsubscribeToken = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Live en cours — CMP Philadelphie',
        );
    }

    public function content(): Content
    {
        $siteUrl = rtrim((string) config('site_public.youtube_live_notify.site_url', url('/')), '/');
        $unsubscribeUrl = $this->unsubscribeToken !== ''
            ? $siteUrl.'/alertes/desabonnement?token='.$this->unsubscribeToken
            : null;

        return new Content(
            view: 'mail.youtube-live-started',
            with: [
                'live' => $this->live,
                'siteUrl' => $siteUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
                'logoUrl' => config('site_public.mail_logo_url') ?: asset('images/logo-cmp.png'),
            ],
        );
    }
}
