<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SiteInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Courriel HTML envoyé à l’équipe d’intercession lors d’une nouvelle requête de prière.
 */
class PrayerRequestSubmittedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SiteInquiry $inquiry,
    ) {}

    /**
     * @return Envelope Sujet et métadonnées du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle requête de prière — CMP Philadelphie',
        );
    }

    /**
     * @return Content Vue HTML du courriel (logo intégré via $message->embed() dans la vue).
     */
    public function content(): Content
    {
        $inquiry = $this->inquiry;

        return new Content(
            view: 'mail.prayer-request-submitted',
            with: [
                'inquiry' => $inquiry,
                'displayName' => $inquiry->is_anonymous ? 'Anonyme' : $inquiry->name,
                'adminUrl' => url('/admin/site-inquiries/'.$inquiry->id),
                'logoPath' => public_path('images/logo-cmp.png'),
                'logoUrl' => config('site_public.mail_logo_url') ?: asset('images/logo-cmp.png'),
            ],
        );
    }
}
