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
 * Envoie le lien du portail d’accueil (après réponse à la fiche).
 */
class GuestPortalLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public GuestPastor $guestPastor,
        public string $portalUrl,
        public string $projectTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CMP — Votre portail d’accueil ('.$this->projectTitle.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.guest-branded',
            with: [
                'subjectLine' => 'Votre portail d’accueil CMP',
                'heading' => 'Merci pour votre réponse',
                'introHtml' => 'Bonjour <strong>'.e($this->guestPastor->full_name).'</strong>,<br>'
                    .'Votre fiche a bien été enregistrée. Accédez à votre portail personnalisé '
                    .'(tenues, équipe, jours d’intervention, liturgie) via le bouton ci-dessous.'
                    .'<br><em>Ce lien reste valable jusqu’à la fin du projet.</em>',
                'pastorName' => $this->guestPastor->full_name,
                'projectTitle' => $this->projectTitle,
                'metaRows' => [],
                'passwordHint' => null,
                'ctaUrl' => $this->portalUrl,
                'ctaLabel' => 'Ouvrir mon portail',
                'logoPath' => public_path('images/logo-cmp.png'),
                'logoUrl' => asset('images/logo-cmp.png'),
                'pastorPhotoPath' => $this->guestPastor->photoAbsolutePath(),
                'pastorPhotoUrl' => $this->guestPastor->photoPublicUrl(),
            ],
        );
    }
}
