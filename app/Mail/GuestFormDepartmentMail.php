<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ChurchDepartment;
use App\Models\GuestInfoSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifie un département qu’une fiche d’accueil invité a été remplie.
 */
class GuestFormDepartmentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  GuestInfoSubmission  $submission  Soumission reçue.
     * @param  ChurchDepartment  $department  Département destinataire.
     * @param  string  $portalUrl  Lien vers le portail réponses.
     * @param  string  $passwordHint  Mot de passe formulaire (en clair) à rappeler.
     */
    public function __construct(
        public GuestInfoSubmission $submission,
        public ChurchDepartment $department,
        public string $portalUrl,
        public string $passwordHint,
    ) {}

    /**
     * Enveloppe du message.
     */
    public function envelope(): Envelope
    {
        $pastor = $this->submission->guestPastor?->full_name ?? 'Pasteur invité';

        return new Envelope(
            subject: 'Fiche renseignement — '.$pastor.' ('.$this->department->name.')',
        );
    }

    /**
     * Contenu HTML brandé CMP.
     */
    public function content(): Content
    {
        $pastor = $this->submission->guestPastor;

        return new Content(
            html: 'mail.guest-branded',
            with: [
                'subjectLine' => 'Fiche renseignement reçue',
                'heading' => 'Fiche renseignement reçue',
                'introHtml' => 'Bonjour <strong>'.e($this->department->name).'</strong>,<br>le pasteur invité a rempli sa fiche. Consultez les réponses liées à votre département.',
                'pastorName' => $pastor?->full_name ?? '—',
                'projectTitle' => $this->submission->form?->project?->title ?? '—',
                'metaRows' => [
                    ['label' => 'Département', 'value' => $this->department->name],
                    ['label' => 'Reçu le', 'value' => $this->submission->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—'],
                ],
                'passwordHint' => $this->passwordHint,
                'ctaUrl' => $this->portalUrl,
                'ctaLabel' => 'Voir les réponses',
                'logoPath' => public_path('images/logo-cmp.png'),
                'logoUrl' => asset('images/logo-cmp.png'),
                'pastorPhotoPath' => $pastor?->photoAbsolutePath(),
                'pastorPhotoUrl' => $pastor?->photoPublicUrl(),
            ],
        );
    }
}
