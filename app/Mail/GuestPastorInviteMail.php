<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\GuestPastor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envoie le lien court du formulaire au pasteur invité.
 */
class GuestPastorInviteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public GuestPastor $guestPastor,
        public string $formUrl,
        public string $formTitle,
    ) {}

    /**
     * Enveloppe du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fiche de renseignements — Centre Missionnaire Philadelphie',
        );
    }

    /**
     * Contenu Blade.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guest-pastor-invite',
            with: [
                'pastorName' => $this->guestPastor->full_name,
                'formUrl' => $this->formUrl,
                'formTitle' => $this->formTitle,
                'projectTitle' => $this->guestPastor->project?->title ?? '—',
            ],
        );
    }
}
