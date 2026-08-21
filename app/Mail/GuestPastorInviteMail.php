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
        public ?string $customSubject = null,
        public ?string $customIntroHtml = null,
    ) {}

    /**
     * Enveloppe du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: filled($this->customSubject)
                ? (string) $this->customSubject
                : 'Fiche de renseignements — Centre Missionnaire Philadelphie',
        );
    }

    /**
     * Contenu HTML brandé CMP.
     */
    public function content(): Content
    {
        $introHtml = filled($this->customIntroHtml)
            ? (string) $this->customIntroHtml
            : 'Bonjour <strong>'.e($this->guestPastor->full_name).'</strong>,<br>afin de mieux préparer votre accueil, merci de remplir la fiche&nbsp;: <em>'.e($this->formTitle).'</em>.';

        return new Content(
            html: 'mail.guest-branded',
            with: [
                'subjectLine' => filled($this->customSubject)
                    ? (string) $this->customSubject
                    : 'Fiche de renseignements CMP',
                'heading' => 'Bienvenue au CMP',
                'introHtml' => $introHtml,
                'pastorName' => $this->guestPastor->full_name,
                'projectTitle' => $this->guestPastor->project?->title ?? '—',
                'metaRows' => array_values(array_filter([
                    filled($this->guestPastor->church_name)
                        ? ['label' => 'Église', 'value' => (string) $this->guestPastor->church_name]
                        : null,
                    $this->guestPastor->arrival_at
                        ? ['label' => 'Arrivée', 'value' => $this->guestPastor->arrival_at->timezone(config('app.timezone'))->format('d/m/Y H:i')]
                        : null,
                ])),
                'passwordHint' => null,
                'ctaUrl' => $this->formUrl,
                'ctaLabel' => 'Remplir la fiche',
                'logoPath' => public_path('images/logo-cmp.png'),
                'logoUrl' => asset('images/logo-cmp.png'),
                'pastorPhotoPath' => $this->guestPastor->photoAbsolutePath(),
                'pastorPhotoUrl' => $this->guestPastor->photoPublicUrl(),
            ],
        );
    }
}
