<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\GuestPastorInviteMail;
use App\Models\GuestInviteDispatch;
use App\Models\GuestPastor;
use App\Models\GuestPastoralProject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

/**
 * Envoie les invitations pasteurs (e-mail, SMS, WhatsApp) et journalise chaque envoi.
 */
final class GuestInviteDispatchService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Modèles de messages par défaut (placeholders : {nom}, {lien}, {fiche}, {projet}).
     *
     * @return array{email_subject: string, email_intro: string, sms_message: string, whatsapp_message: string}
     */
    public function defaultMessageTemplates(GuestPastoralProject $project, string $formTitle): array
    {
        $projectTitle = $project->title;

        return [
            'email_subject' => 'Fiche de renseignements — Centre Missionnaire Philadelphie',
            'email_intro' => 'Bonjour {nom},'."\n\n"
                .'Afin de mieux préparer votre accueil au Centre Missionnaire Philadelphie'
                .' (projet « {projet} »), merci de remplir la fiche « {fiche} ».'."\n\n"
                .'Lien : {lien}',
            'sms_message' => 'CMP Philadelphie: fiche renseignement pasteur invite ({fiche}). Remplir ici: {lien}',
            'whatsapp_message' => 'Bonjour {nom},'."\n\n"
                .'Centre Missionnaire Philadelphie — projet « {projet} ».'."\n"
                .'Merci de remplir votre fiche de renseignements « {fiche} » :'."\n"
                .'{lien}',
        ];
    }

    /**
     * Remplace les placeholders d’un modèle pour un pasteur.
     */
    public function renderTemplate(string $template, GuestPastor $pastor, string $formTitle, ?string $projectTitle = null): string
    {
        return str_replace(
            ['{nom}', '{lien}', '{fiche}', '{projet}'],
            [
                $pastor->full_name,
                $pastor->shortFormUrl(),
                $formTitle,
                $projectTitle ?? ($pastor->project?->title ?? ''),
            ],
            $template,
        );
    }

    /**
     * Estimation du nombre de SMS (après normalisation GSM / accents).
     *
     * @return array{length: int, max: int, segments: int, preview: string}
     */
    public function estimateSms(string $message): array
    {
        return $this->smsSender->estimateSegments($message);
    }

    /**
     * Construit l’URL wa.me cliquable pour un pasteur.
     */
    public function buildWhatsAppUrl(GuestPastor $pastor, string $message): ?string
    {
        $digits = $this->normalizePhoneDigits((string) ($pastor->phone ?? ''));
        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /**
     * Envoie un seul canal pour une sélection de pasteurs, avec messages personnalisés.
     *
     * @param  list<int>  $pastorIds  Vide = tous.
     * @return array{
     *     sent: int,
     *     failed: int,
     *     skipped: int,
     *     whatsapp_links: list<array{name: string, url: string, phone: string}>,
     *     messages: list<string>,
     *     whatsapp_html: HtmlString|null
     * }
     */
    public function dispatchChannel(
        GuestPastoralProject $project,
        array $pastorIds,
        string $channel,
        ?User $actor = null,
        ?string $emailSubject = null,
        ?string $emailIntro = null,
        ?string $smsMessage = null,
        ?string $whatsappMessage = null,
        bool $attachPdfLetter = false,
    ): array {
        $form = $project->form;
        if ($form === null || ! $form->is_published) {
            return $this->emptyResult(['Formulaire manquant ou non publié.']);
        }

        if (! in_array($channel, [
            GuestInviteDispatch::CHANNEL_EMAIL,
            GuestInviteDispatch::CHANNEL_SMS,
            GuestInviteDispatch::CHANNEL_WHATSAPP,
        ], true)) {
            return $this->emptyResult(['Canal inconnu.']);
        }

        $defaults = $this->defaultMessageTemplates($project, $form->title);
        $emailSubject = filled($emailSubject) ? (string) $emailSubject : $defaults['email_subject'];
        $emailIntro = filled($emailIntro) ? (string) $emailIntro : $defaults['email_intro'];
        $smsMessage = filled($smsMessage) ? (string) $smsMessage : $defaults['sms_message'];
        $whatsappMessage = filled($whatsappMessage) ? (string) $whatsappMessage : $defaults['whatsapp_message'];

        $pastorsQuery = $project->guestPastors()->orderBy('full_name');
        if ($pastorIds !== []) {
            $pastorsQuery->whereIn('id', $pastorIds);
        }

        /** @var Collection<int, GuestPastor> $pastors */
        $pastors = $pastorsQuery->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $whatsappLinks = [];
        $messages = [];

        foreach ($pastors as $pastor) {
            $result = match ($channel) {
                GuestInviteDispatch::CHANNEL_EMAIL => $this->sendEmail(
                    $project,
                    $pastor,
                    $form->title,
                    $emailSubject,
                    $emailIntro,
                    $actor,
                    $attachPdfLetter,
                ),
                GuestInviteDispatch::CHANNEL_SMS => $this->sendSms(
                    $project,
                    $pastor,
                    $form->title,
                    $smsMessage,
                    $actor,
                ),
                GuestInviteDispatch::CHANNEL_WHATSAPP => $this->prepareWhatsApp(
                    $project,
                    $pastor,
                    $form->title,
                    $whatsappMessage,
                    $actor,
                ),
                default => ['status' => GuestInviteDispatch::STATUS_SKIPPED, 'message' => 'Canal inconnu'],
            };

            match ($result['status']) {
                GuestInviteDispatch::STATUS_SENT, GuestInviteDispatch::STATUS_LINK_READY => $sent++,
                GuestInviteDispatch::STATUS_FAILED => $failed++,
                default => $skipped++,
            };

            if (($result['whatsapp_url'] ?? null) !== null) {
                $whatsappLinks[] = [
                    'name' => $pastor->full_name,
                    'url' => (string) $result['whatsapp_url'],
                    'phone' => (string) ($result['recipient'] ?? ''),
                ];
            }

            if (filled($result['message'] ?? null)) {
                $messages[] = $pastor->full_name.' : '.$result['message'];
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'whatsapp_links' => $whatsappLinks,
            'messages' => $messages,
            'whatsapp_html' => $this->whatsappLinksHtml($whatsappLinks),
        ];
    }

    /**
     * @param  list<array{name: string, url: string, phone: string}>  $links
     */
    public function whatsappLinksHtml(array $links): ?HtmlString
    {
        if ($links === []) {
            return null;
        }

        $items = [];
        foreach ($links as $link) {
            $items[] = '<li style="margin:0.4rem 0;">'
                .'<strong>'.e($link['name']).'</strong> — '
                .'<a href="'.e($link['url']).'" target="_blank" rel="noopener noreferrer" '
                .'style="color:#128c7e;font-weight:600;text-decoration:underline;">'
                .'Ouvrir WhatsApp</a>'
                .(filled($link['phone']) ? ' <span style="color:#6b7280;">(+'.e($link['phone']).')</span>' : '')
                .'</li>';
        }

        return new HtmlString(
            '<p style="margin:0 0 0.5rem;font-weight:600;">Liens WhatsApp (cliquez pour envoyer) :</p>'
            .'<ul style="margin:0;padding-left:1.1rem;">'.implode('', $items).'</ul>'
        );
    }

    /**
     * @param  list<string>  $messages
     * @return array{sent: int, failed: int, skipped: int, whatsapp_links: list<array{name: string, url: string, phone: string}>, messages: list<string>, whatsapp_html: null}
     */
    private function emptyResult(array $messages): array
    {
        return [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'whatsapp_links' => [],
            'messages' => $messages,
            'whatsapp_html' => null,
        ];
    }

    /**
     * @return array{status: string, message?: string, recipient?: string}
     */
    private function sendEmail(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $formTitle,
        string $subject,
        string $introTemplate,
        ?User $actor,
        bool $attachPdfLetter = false,
    ): array {
        if (! filled($pastor->email)) {
            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_EMAIL,
                null,
                GuestInviteDispatch::STATUS_SKIPPED,
                null,
                ['reason' => 'email_missing'],
                $actor,
            );

            return ['status' => GuestInviteDispatch::STATUS_SKIPPED, 'message' => 'E-mail manquant'];
        }

        $introText = $this->renderTemplate($introTemplate, $pastor, $formTitle, $project->title);
        $introHtml = nl2br(e($introText));
        $formUrl = $pastor->shortFormUrl();

        $pdfPath = null;
        if ($attachPdfLetter) {
            try {
                $pdfPath = app(GuestInvitationLetterService::class)->absolutePdfPathForPastor($pastor, $actor);
            } catch (\Throwable) {
                $pdfPath = null;
            }
        }

        try {
            Mail::to($pastor->email)->send(new GuestPastorInviteMail(
                $pastor,
                $formUrl,
                $formTitle,
                $subject,
                $introHtml,
                $pdfPath,
            ));

            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_EMAIL,
                (string) $pastor->email,
                GuestInviteDispatch::STATUS_SENT,
                mb_substr($introText, 0, 480),
                [
                    'subject' => $subject,
                    'form_url' => $formUrl,
                    'pdf_attached' => $pdfPath !== null,
                ],
                $actor,
            );

            return ['status' => GuestInviteDispatch::STATUS_SENT, 'recipient' => (string) $pastor->email];
        } catch (\Throwable $e) {
            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_EMAIL,
                (string) $pastor->email,
                GuestInviteDispatch::STATUS_FAILED,
                null,
                ['error' => $e->getMessage()],
                $actor,
            );

            return ['status' => GuestInviteDispatch::STATUS_FAILED, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string, recipient?: string}
     */
    private function sendSms(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $formTitle,
        string $smsTemplate,
        ?User $actor,
    ): array {
        if (! filled($pastor->phone)) {
            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_SMS,
                null,
                GuestInviteDispatch::STATUS_SKIPPED,
                null,
                ['reason' => 'phone_missing'],
                $actor,
            );

            return ['status' => GuestInviteDispatch::STATUS_SKIPPED, 'message' => 'Téléphone manquant'];
        }

        $body = $this->renderTemplate($smsTemplate, $pastor, $formTitle, $project->title);
        $estimate = $this->estimateSms($body);
        $result = $this->smsSender->send((string) $pastor->phone, $body, fitToSingle: false);
        $status = $result->success
            ? GuestInviteDispatch::STATUS_SENT
            : GuestInviteDispatch::STATUS_FAILED;

        $this->logDispatch(
            $project,
            $pastor,
            GuestInviteDispatch::CHANNEL_SMS,
            (string) $pastor->phone,
            $status,
            mb_substr($body, 0, 480),
            [
                'sms_status' => $result->status,
                'error' => $result->error,
                'response' => $result->response,
                'segments' => $estimate['segments'],
                'length' => $estimate['length'],
                'form_url' => $pastor->shortFormUrl(),
            ],
            $actor,
        );

        return [
            'status' => $status,
            'recipient' => (string) $pastor->phone,
            'message' => $result->success ? null : ($result->error ?? 'Échec SMS'),
        ];
    }

    /**
     * @return array{status: string, message?: string, whatsapp_url?: string, recipient?: string}
     */
    private function prepareWhatsApp(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $formTitle,
        string $whatsappTemplate,
        ?User $actor,
    ): array {
        $text = $this->renderTemplate($whatsappTemplate, $pastor, $formTitle, $project->title);
        $url = $this->buildWhatsAppUrl($pastor, $text);

        if ($url === null) {
            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_WHATSAPP,
                null,
                GuestInviteDispatch::STATUS_SKIPPED,
                null,
                ['reason' => 'phone_missing'],
                $actor,
            );

            return ['status' => GuestInviteDispatch::STATUS_SKIPPED, 'message' => 'Téléphone manquant pour WhatsApp'];
        }

        $digits = $this->normalizePhoneDigits((string) $pastor->phone);

        $this->logDispatch(
            $project,
            $pastor,
            GuestInviteDispatch::CHANNEL_WHATSAPP,
            $digits,
            GuestInviteDispatch::STATUS_LINK_READY,
            mb_substr($text, 0, 480),
            ['whatsapp_url' => $url, 'form_url' => $pastor->shortFormUrl()],
            $actor,
        );

        return [
            'status' => GuestInviteDispatch::STATUS_LINK_READY,
            'whatsapp_url' => $url,
            'recipient' => $digits,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function logDispatch(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $channel,
        ?string $recipient,
        string $status,
        ?string $preview,
        ?array $meta,
        ?User $actor,
    ): void {
        GuestInviteDispatch::query()->create([
            'guest_pastoral_project_id' => $project->id,
            'guest_pastor_id' => $pastor->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => $status,
            'message_preview' => $preview,
            'meta' => $meta,
            'sent_by_user_id' => $actor?->id,
            'sent_at' => now(),
        ]);
    }

    /**
     * Normalise un numéro pour WhatsApp (chiffres internationaux, ex. 243…).
     */
    private function normalizePhoneDigits(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '243'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '8')) {
            $digits = '243'.$digits;
        }

        return $digits;
    }
}
