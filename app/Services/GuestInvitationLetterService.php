<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestInvitationLetter;
use App\Models\GuestPastor;
use App\Models\GuestPastoralProject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Résout et génère les lettres d’invitation PDF (modèle projet + override pasteur).
 */
final class GuestInvitationLetterService
{
    /**
     * Retourne le contenu effectif pour un pasteur (override sinon modèle projet).
     */
    public function resolveForPastor(GuestPastor $pastor): ?GuestInvitationLetter
    {
        $pastor->loadMissing('project', 'invitationLetter');

        if ($pastor->invitationLetter !== null) {
            return $pastor->invitationLetter;
        }

        return $pastor->project?->projectLetterTemplate();
    }

    /**
     * Crée ou met à jour le modèle projet.
     */
    public function ensureProjectTemplate(GuestPastoralProject $project): GuestInvitationLetter
    {
        $existing = $project->projectLetterTemplate();
        if ($existing !== null) {
            return $existing;
        }

        return GuestInvitationLetter::query()->create([
            'project_id' => $project->id,
            'guest_pastor_id' => null,
            'recipient_title' => 'À {titre_nom}',
            'body_html' => $this->defaultBodyHtml(),
            'signature_html' => '<p>Bienvenue chez vous !<br>Avec toute notre affection fraternelle,<br><strong>Couple Pastoral Ken et Nathalie LUAMBA</strong></p>',
            'status' => GuestInvitationLetter::STATUS_DRAFT,
        ]);
    }

    /**
     * Génère le PDF pour une lettre (éventuellement liée à un pasteur).
     *
     * @return string Chemin relatif public disk
     */
    public function generatePdf(GuestInvitationLetter $letter, ?User $actor = null, ?GuestPastor $pastor = null): string
    {
        $letter->loadMissing('project', 'guestPastor');
        $project = $letter->project;
        $pastor ??= $letter->guestPastor;

        $recipientTitle = $this->applyPlaceholders(
            (string) ($letter->recipient_title ?: 'À {titre_nom}'),
            $project,
            $pastor,
            $letter,
        );
        $bodyHtml = $this->applyPlaceholders((string) ($letter->body_html ?? ''), $project, $pastor, $letter);
        $signatureHtml = $this->applyPlaceholders((string) ($letter->signature_html ?? ''), $project, $pastor, $letter);

        $dates = '';
        if ($project?->starts_at && $project->ends_at) {
            $dates = $project->starts_at->format('d/m/Y').' — '.$project->ends_at->format('d/m/Y');
        } elseif ($project?->starts_at) {
            $dates = $project->starts_at->format('d/m/Y');
        }

        $pdf = Pdf::loadView('pdf.guest-invitation-letter', [
            'projectTitle' => $project?->title ?? 'CMP',
            'dates' => $dates,
            'recipientTitle' => $recipientTitle,
            'bodyHtml' => $bodyHtml,
            'signatureHtml' => $signatureHtml,
            'headerImagePath' => $letter->header_image_path
                ? storage_path('app/public/'.ltrim((string) $letter->header_image_path, '/'))
                : null,
            'logoPath' => public_path('images/logo-cmp.png'),
        ])->setPaper('a4');

        $slug = $pastor !== null
            ? 'pastor-'.$pastor->id
            : 'project-'.($project?->id ?? 'x');
        $relative = 'guest-letters/'.$slug.'-'.Str::lower(Str::random(6)).'.pdf';
        Storage::disk('public')->put($relative, $pdf->output());

        $letter->update([
            'pdf_path' => $relative,
            'status' => GuestInvitationLetter::STATUS_GENERATED,
            'generated_at' => now(),
            'generated_by_user_id' => $actor?->id,
        ]);

        return $relative;
    }

    /**
     * Garantit un PDF à jour pour un pasteur et retourne le chemin absolu (pour PJ mail).
     */
    public function absolutePdfPathForPastor(GuestPastor $pastor, ?User $actor = null): ?string
    {
        $letter = $this->resolveForPastor($pastor);
        if ($letter === null) {
            return null;
        }

        if (blank($letter->pdf_path) || ! Storage::disk('public')->exists((string) $letter->pdf_path)) {
            $this->generatePdf($letter, $actor, $pastor);
            $letter->refresh();
        }

        if (blank($letter->pdf_path)) {
            return null;
        }

        $absolute = storage_path('app/public/'.ltrim((string) $letter->pdf_path, '/'));

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Remplace les placeholders de la lettre.
     */
    public function applyPlaceholders(
        string $html,
        ?GuestPastoralProject $project,
        ?GuestPastor $pastor,
        GuestInvitationLetter $letter,
    ): string {
        $titreNom = $pastor?->full_name ?? 'Pasteur invité';
        $theme = $project?->notes ?? $project?->title ?? '';
        $dates = '';
        if ($project?->starts_at && $project->ends_at) {
            $dates = $project->starts_at->format('d/m/Y').' au '.$project->ends_at->format('d/m/Y');
        }

        return str_replace(
            ['{titre_nom}', '{projet}', '{theme}', '{dates}', '{signature}'],
            [
                $titreNom,
                $project?->title ?? '',
                $theme,
                $dates,
                strip_tags((string) ($letter->signature_html ?? '')),
            ],
            $html,
        );
    }

    private function defaultBodyHtml(): string
    {
        return <<<'HTML'
<p>Cher Pasteur,</p>
<p>C’est avec une grande joie que nous vous accueillons à Kinshasa, au nom du Centre Missionnaire Philadelphie, pour le projet « {projet} » ({dates}).</p>
<p>Nous rendons grâce à Dieu pour votre ministère et prions que votre séjour soit une bénédiction pour l’église et pour vous.</p>
<p>Bienvenue chez vous !</p>
HTML;
    }
}
