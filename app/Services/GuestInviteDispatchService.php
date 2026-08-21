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

/**
 * Envoie les invitations pasteurs (e-mail, SMS, WhatsApp) et journalise chaque envoi.
 */
final class GuestInviteDispatchService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Envoie les invitations aux pasteurs choisis via les canaux demandés.
     *
     * @param  list<int>  $pastorIds  Vide = tous les pasteurs du projet.
     * @param  list<string>  $channels  email|sms|whatsapp
     * @return array{
     *     sent: int,
     *     failed: int,
     *     skipped: int,
     *     whatsapp_links: list<array{name: string, url: string, phone: string}>,
     *     messages: list<string>
     * }
     */
    public function dispatch(
        GuestPastoralProject $project,
        array $pastorIds,
        array $channels,
        ?User $actor = null,
    ): array {
        $form = $project->form;
        if ($form === null || ! $form->is_published) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'whatsapp_links' => [],
                'messages' => ['Formulaire manquant ou non publié.'],
            ];
        }

        $channels = array_values(array_intersect(
            $channels,
            [
                GuestInviteDispatch::CHANNEL_EMAIL,
                GuestInviteDispatch::CHANNEL_SMS,
                GuestInviteDispatch::CHANNEL_WHATSAPP,
            ],
        ));

        if ($channels === []) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'whatsapp_links' => [],
                'messages' => ['Aucun canal sélectionné.'],
            ];
        }

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
            foreach ($channels as $channel) {
                $result = match ($channel) {
                    GuestInviteDispatch::CHANNEL_EMAIL => $this->sendEmail($project, $pastor, $form->title, $actor),
                    GuestInviteDispatch::CHANNEL_SMS => $this->sendSms($project, $pastor, $form->title, $actor),
                    GuestInviteDispatch::CHANNEL_WHATSAPP => $this->prepareWhatsApp($project, $pastor, $form->title, $actor),
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
                    $messages[] = $pastor->full_name.' ('.$channel.') : '.$result['message'];
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'whatsapp_links' => $whatsappLinks,
            'messages' => $messages,
        ];
    }

    /**
     * @return array{status: string, message?: string, whatsapp_url?: string, recipient?: string}
     */
    private function sendEmail(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $formTitle,
        ?User $actor,
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

        try {
            Mail::to($pastor->email)->send(new GuestPastorInviteMail(
                $pastor,
                $pastor->publicFormUrl(),
                $formTitle,
            ));

            $preview = 'Invitation e-mail — '.$formTitle;
            $this->logDispatch(
                $project,
                $pastor,
                GuestInviteDispatch::CHANNEL_EMAIL,
                (string) $pastor->email,
                GuestInviteDispatch::STATUS_SENT,
                $preview,
                null,
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

        $body = $this->smsSender->fitSingleSms(
            'CMP Philadelphie: merci de remplir votre fiche ('.$formTitle.'). '.$pastor->publicFormUrl()
        );

        $result = $this->smsSender->send((string) $pastor->phone, $body);
        $status = $result->success
            ? GuestInviteDispatch::STATUS_SENT
            : GuestInviteDispatch::STATUS_FAILED;

        $this->logDispatch(
            $project,
            $pastor,
            GuestInviteDispatch::CHANNEL_SMS,
            (string) $pastor->phone,
            $status,
            $body,
            [
                'sms_status' => $result->status,
                'error' => $result->error,
                'response' => $result->response,
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
     * Prépare un lien wa.me (pas d’API WhatsApp Business) et le journalise.
     *
     * @return array{status: string, message?: string, whatsapp_url?: string, recipient?: string}
     */
    private function prepareWhatsApp(
        GuestPastoralProject $project,
        GuestPastor $pastor,
        string $formTitle,
        ?User $actor,
    ): array {
        $digits = $this->normalizePhoneDigits((string) ($pastor->phone ?? ''));
        if ($digits === '') {
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

        $text = 'Bonjour '.$pastor->full_name.', Centre Missionnaire Philadelphie. '
            .'Merci de remplir votre fiche « '.$formTitle.' » : '.$pastor->publicFormUrl();
        $url = 'https://wa.me/'.$digits.'?text='.rawurlencode($text);

        $this->logDispatch(
            $project,
            $pastor,
            GuestInviteDispatch::CHANNEL_WHATSAPP,
            $digits,
            GuestInviteDispatch::STATUS_LINK_READY,
            mb_substr($text, 0, 480),
            ['whatsapp_url' => $url],
            $actor,
        );

        return [
            'status' => GuestInviteDispatch::STATUS_LINK_READY,
            'whatsapp_url' => $url,
            'recipient' => $digits,
            'message' => 'Ouvrir le lien WhatsApp pour envoyer',
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
