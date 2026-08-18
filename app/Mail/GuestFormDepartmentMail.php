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
     * Contenu Blade.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guest-form-department',
            with: [
                'pastorName' => $this->submission->guestPastor?->full_name ?? '—',
                'departmentName' => $this->department->name,
                'projectTitle' => $this->submission->form?->project?->title ?? '—',
                'portalUrl' => $this->portalUrl,
                'passwordHint' => $this->passwordHint,
                'submittedAt' => $this->submission->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ],
        );
    }
}
