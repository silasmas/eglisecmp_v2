<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Testimony;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Courriel envoyé au fidèle lorsque son témoignage est approuvé et publié.
 */
class TestimonyApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Testimony $testimony,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre témoignage est publié — CMP Philadelphie',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.testimony-approved',
            with: [
                'testimony' => $this->testimony,
                'wallUrl' => url('/temoignages'),
                'logoUrl' => config('site_public.mail_logo_url') ?: asset('images/logo-cmp.png'),
            ],
        );
    }
}
