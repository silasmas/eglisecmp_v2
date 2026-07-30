<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail OTP pour l'inscription ouvrier CMP.
 */
class WorkerEmailOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $code  Code OTP à 6 chiffres.
     * @param  int  $ttlMinutes  Durée de validité en minutes.
     */
    public function __construct(
        public string $code,
        public int $ttlMinutes = 15,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CMP — Code de vérification pour votre inscription ouvrier',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.worker-email-otp',
            with: [
                'code' => $this->code,
                'ttlMinutes' => $this->ttlMinutes,
                'logoUrl' => config('site_public.mail_logo_url') ?: asset('images/logo-cmp.png'),
                'siteName' => 'Centre Missionnaire Philadelphie',
            ],
        );
    }
}
