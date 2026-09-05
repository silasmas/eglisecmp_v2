<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\GuestFormDepartmentMail;
use App\Models\ChurchDepartment;
use App\Models\GuestDepartmentNotification;
use App\Models\GuestInfoForm;
use App\Models\GuestInfoSubmission;
use App\Models\GuestPastor;
use App\Models\User;
use App\Support\GuestFormAnswerScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Enregistre une soumission et notifie les départements concernés.
 */
final class GuestFormSubmissionService
{
    public function __construct(
        private readonly SmsSender $smsSender,
        private readonly GuestPortalDispatchService $portalDispatch,
    ) {}

    /**
     * Crée la soumission, marque le pasteur, notifie départements + lien portail.
     *
     * @param  array<string, mixed>  $payload
     */
    public function submit(GuestPastor $pastor, GuestInfoForm $form, array $payload): GuestInfoSubmission
    {
        $submission = GuestInfoSubmission::query()->updateOrCreate(
            [
                'guest_pastor_id' => $pastor->id,
                'form_id' => $form->id,
            ],
            [
                'payload' => $payload,
                'submitted_at' => now(),
                'access_token' => Str::lower(Str::random(32)),
            ],
        );

        $submission->ensurePortalToken();

        $pastor->update([
            'form_submitted_at' => now(),
        ]);

        $this->notifyDepartments($submission, $form);

        $channels = [];
        if (filled($pastor->email)) {
            $channels[] = GuestPortalDispatchService::CHANNEL_EMAIL;
        }
        if (filled($pastor->phone)) {
            $channels[] = GuestPortalDispatchService::CHANNEL_SMS;
        }
        if ($channels !== []) {
            try {
                $this->portalDispatch->dispatch($submission->fresh() ?? $submission, $channels);
            } catch (\Throwable) {
                // Best-effort : la soumission reste valide même si l’envoi portail échoue.
            }
        }

        return $submission->refresh();
    }

    /**
     * Notifie chaque département lié (e-mail par défaut).
     *
     * @param  list<int>|null  $onlyDepartmentIds
     * @param  list<string>  $channels
     * @return array{sent: int, failed: int, skipped: int, whatsapp_links: list<array{name: string, url: string}>, whatsapp_html: HtmlString|null}
     */
    public function notifyDepartments(
        GuestInfoSubmission $submission,
        GuestInfoForm $form,
        ?User $actor = null,
        ?array $onlyDepartmentIds = null,
        array $channels = [GuestDepartmentNotification::CHANNEL_EMAIL],
    ): array {
        $departmentIds = $onlyDepartmentIds ?? $this->collectDepartmentIds($form);
        if ($departmentIds === []) {
            $departmentIds = $form->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        }

        $channels = array_values(array_intersect($channels, [
            GuestDepartmentNotification::CHANNEL_EMAIL,
            GuestDepartmentNotification::CHANNEL_SMS,
            GuestDepartmentNotification::CHANNEL_WHATSAPP,
        ]));
        if ($channels === []) {
            $channels = [GuestDepartmentNotification::CHANNEL_EMAIL];
        }

        $passwordHint = (string) Cache::get($this->passwordCacheKey($form->id), 'Voir l’administration CMP');
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $whatsappLinks = [];

        foreach ($departmentIds as $departmentId) {
            $department = ChurchDepartment::query()->with(['manager', 'managers.user'])->find($departmentId);
            if ($department === null) {
                $skipped++;

                continue;
            }

            $filtered = GuestFormAnswerScope::visiblePayloadForDepartment($submission, (int) $departmentId);
            if ($filtered === [] && $form->project?->departments()->where('church_departments.id', $departmentId)->exists() !== true) {
                $skipped++;

                continue;
            }

            foreach ($channels as $channel) {
                $result = match ($channel) {
                    GuestDepartmentNotification::CHANNEL_EMAIL => $this->sendDepartmentEmail(
                        $submission,
                        $department,
                        $passwordHint,
                        $actor,
                    ),
                    GuestDepartmentNotification::CHANNEL_SMS => $this->sendDepartmentSms(
                        $submission,
                        $department,
                        $passwordHint,
                        $actor,
                    ),
                    GuestDepartmentNotification::CHANNEL_WHATSAPP => $this->prepareDepartmentWhatsApp(
                        $submission,
                        $department,
                        $passwordHint,
                        $actor,
                    ),
                    default => ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'whatsapp_links' => []],
                };

                $sent += $result['sent'];
                $failed += $result['failed'];
                $skipped += $result['skipped'];
                foreach ($result['whatsapp_links'] as $link) {
                    $whatsappLinks[] = $link;
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'whatsapp_links' => $whatsappLinks,
            'whatsapp_html' => $this->whatsappLinksHtml($whatsappLinks),
        ];
    }

    /**
     * IDs des départements concernés par une soumission (pour l’UI Filament).
     *
     * @return list<int>
     */
    public function departmentIdsForSubmission(GuestInfoSubmission $submission): array
    {
        $form = $submission->form;
        if ($form === null) {
            return [];
        }

        $ids = $this->collectDepartmentIds($form);
        if ($ids === []) {
            $ids = $form->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        }

        return $ids;
    }

    /**
     * Accuse réception pour un département sur une soumission.
     *
     * @return array{ok: bool, acknowledged_at: string|null, message: string}
     */
    public function acknowledgeDepartment(
        GuestInfoSubmission $submission,
        int $departmentId,
        ?string $acknowledgerName = null,
        string $via = GuestDepartmentNotification::ACK_VIA_PORTAL,
    ): array {
        $query = GuestDepartmentNotification::query()
            ->where('guest_info_submission_id', $submission->id)
            ->where('church_department_id', $departmentId)
            ->where('status', GuestDepartmentNotification::STATUS_SENT);

        if (! $query->exists()) {
            $notification = GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $departmentId,
                'channel' => GuestDepartmentNotification::CHANNEL_EMAIL,
                'recipient' => null,
                'status' => GuestDepartmentNotification::STATUS_SENT,
                'meta' => ['created_on_ack' => true],
                'sent_at' => $submission->submitted_at ?? now(),
                'acknowledged_at' => now(),
                'acknowledged_by_name' => filled($acknowledgerName) ? trim((string) $acknowledgerName) : null,
                'acknowledged_via' => $via,
            ]);

            return [
                'ok' => true,
                'acknowledged_at' => $notification->acknowledged_at?->toIso8601String(),
                'message' => 'Réception accusée. Merci.',
            ];
        }

        $name = filled($acknowledgerName) ? trim((string) $acknowledgerName) : null;
        $now = now();

        $query->whereNull('acknowledged_at')->update([
            'acknowledged_at' => $now,
            'acknowledged_by_name' => $name,
            'acknowledged_via' => $via,
        ]);

        $latest = GuestDepartmentNotification::query()
            ->where('guest_info_submission_id', $submission->id)
            ->where('church_department_id', $departmentId)
            ->whereNotNull('acknowledged_at')
            ->orderByDesc('acknowledged_at')
            ->first();

        return [
            'ok' => true,
            'acknowledged_at' => $latest?->acknowledged_at?->toIso8601String() ?? $now->toIso8601String(),
            'message' => 'Réception accusée. Merci.',
        ];
    }

    /**
     * Statut d’accusé de réception pour un département.
     *
     * @return array{acknowledged: bool, acknowledged_at: string|null, acknowledged_by_name: string|null, sent_count: int}
     */
    public function departmentAckStatus(GuestInfoSubmission $submission, int $departmentId): array
    {
        $rows = GuestDepartmentNotification::query()
            ->where('guest_info_submission_id', $submission->id)
            ->where('church_department_id', $departmentId)
            ->orderByDesc('sent_at')
            ->get();

        $acked = $rows->first(fn (GuestDepartmentNotification $n): bool => $n->acknowledged_at !== null);

        return [
            'acknowledged' => $acked !== null,
            'acknowledged_at' => $acked?->acknowledged_at?->toIso8601String(),
            'acknowledged_by_name' => $acked?->acknowledged_by_name,
            'sent_count' => $rows->where('status', GuestDepartmentNotification::STATUS_SENT)->count(),
        ];
    }

    /**
     * Mémorise le mot de passe en clair (pour les mails) après configuration admin.
     */
    public function rememberPlainPassword(GuestInfoForm $form, string $plain): void
    {
        Cache::put($this->passwordCacheKey($form->id), $plain, now()->addYear());
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, whatsapp_links: list<array{name: string, url: string}>}
     */
    private function sendDepartmentEmail(
        GuestInfoSubmission $submission,
        ChurchDepartment $department,
        string $passwordHint,
        ?User $actor,
    ): array {
        $portalUrl = $submission->shortResponsesUrl((int) $department->id);
        $recipients = $this->departmentEmailRecipients($department);
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        if ($recipients === []) {
            GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $department->id,
                'channel' => GuestDepartmentNotification::CHANNEL_EMAIL,
                'recipient' => null,
                'status' => GuestDepartmentNotification::STATUS_SKIPPED,
                'meta' => ['reason' => 'no_email', 'portal_url' => $portalUrl],
                'sent_by_user_id' => $actor?->id,
                'sent_at' => now(),
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'whatsapp_links' => []];
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new GuestFormDepartmentMail(
                    $submission,
                    $department,
                    $portalUrl,
                    $passwordHint,
                ));

                GuestDepartmentNotification::query()->create([
                    'guest_info_submission_id' => $submission->id,
                    'church_department_id' => $department->id,
                    'channel' => GuestDepartmentNotification::CHANNEL_EMAIL,
                    'recipient' => $email,
                    'status' => GuestDepartmentNotification::STATUS_SENT,
                    'meta' => ['portal_url' => $portalUrl],
                    'sent_by_user_id' => $actor?->id,
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                GuestDepartmentNotification::query()->create([
                    'guest_info_submission_id' => $submission->id,
                    'church_department_id' => $department->id,
                    'channel' => GuestDepartmentNotification::CHANNEL_EMAIL,
                    'recipient' => $email,
                    'status' => GuestDepartmentNotification::STATUS_FAILED,
                    'meta' => ['error' => $e->getMessage(), 'portal_url' => $portalUrl],
                    'sent_by_user_id' => $actor?->id,
                    'sent_at' => now(),
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped, 'whatsapp_links' => []];
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, whatsapp_links: list<array{name: string, url: string}>}
     */
    private function sendDepartmentSms(
        GuestInfoSubmission $submission,
        ChurchDepartment $department,
        string $passwordHint,
        ?User $actor,
    ): array {
        $phones = $this->departmentPhoneRecipients($department);
        $portalUrl = $submission->shortResponsesUrl((int) $department->id);

        if ($phones === []) {
            GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $department->id,
                'channel' => GuestDepartmentNotification::CHANNEL_SMS,
                'recipient' => null,
                'status' => GuestDepartmentNotification::STATUS_SKIPPED,
                'meta' => ['reason' => 'no_phone', 'portal_url' => $portalUrl],
                'sent_by_user_id' => $actor?->id,
                'sent_at' => now(),
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'whatsapp_links' => []];
        }

        $pastor = $submission->guestPastor?->full_name ?? 'pasteur invite';
        $body = $this->smsSender->fitSingleSms(
            'CMP Philadelphie: reponses fiche '.$pastor.' ('.$department->name.'). Mot de passe: '.$passwordHint.'. Lien: '.$portalUrl
        );

        $sent = 0;
        $failed = 0;

        foreach ($phones as $phone) {
            $result = $this->smsSender->send($phone, $body, fitToSingle: true);
            $status = $result->success
                ? GuestDepartmentNotification::STATUS_SENT
                : GuestDepartmentNotification::STATUS_FAILED;

            GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $department->id,
                'channel' => GuestDepartmentNotification::CHANNEL_SMS,
                'recipient' => $phone,
                'status' => $status,
                'meta' => [
                    'portal_url' => $portalUrl,
                    'sms_status' => $result->status,
                    'error' => $result->error,
                    'message_preview' => $body,
                ],
                'sent_by_user_id' => $actor?->id,
                'sent_at' => now(),
            ]);

            if ($result->success) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => 0,
            'whatsapp_links' => [],
        ];
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, whatsapp_links: list<array{name: string, url: string}>}
     */
    private function prepareDepartmentWhatsApp(
        GuestInfoSubmission $submission,
        ChurchDepartment $department,
        string $passwordHint,
        ?User $actor,
    ): array {
        $phones = $this->departmentPhoneRecipients($department);
        $portalUrl = $submission->shortResponsesUrl((int) $department->id);
        $whatsappLinks = [];
        $sent = 0;
        $skipped = 0;

        if ($phones === []) {
            GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $department->id,
                'channel' => GuestDepartmentNotification::CHANNEL_WHATSAPP,
                'recipient' => null,
                'status' => GuestDepartmentNotification::STATUS_SKIPPED,
                'meta' => ['reason' => 'no_phone', 'portal_url' => $portalUrl],
                'sent_by_user_id' => $actor?->id,
                'sent_at' => now(),
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'whatsapp_links' => []];
        }

        $pastor = $submission->guestPastor?->full_name ?? 'pasteur invité';
        $text = "Bonjour {$department->name},\n\n"
            ."Centre Missionnaire Philadelphie — les réponses de la fiche « {$pastor} » sont disponibles.\n"
            ."Mot de passe : {$passwordHint}\n"
            ."Lien : {$portalUrl}";

        foreach ($phones as $phone) {
            $digits = $this->normalizePhoneDigits($phone);
            if ($digits === '') {
                $skipped++;

                continue;
            }

            $url = 'https://wa.me/'.$digits.'?text='.rawurlencode($text);

            GuestDepartmentNotification::query()->create([
                'guest_info_submission_id' => $submission->id,
                'church_department_id' => $department->id,
                'channel' => GuestDepartmentNotification::CHANNEL_WHATSAPP,
                'recipient' => $digits,
                'status' => GuestDepartmentNotification::STATUS_SENT,
                'meta' => [
                    'portal_url' => $portalUrl,
                    'whatsapp_url' => $url,
                    'message_preview' => mb_substr($text, 0, 480),
                ],
                'sent_by_user_id' => $actor?->id,
                'sent_at' => now(),
            ]);

            $whatsappLinks[] = [
                'name' => $department->name.' ('.$digits.')',
                'url' => $url,
            ];
            $sent++;
        }

        return [
            'sent' => $sent,
            'failed' => 0,
            'skipped' => $skipped,
            'whatsapp_links' => $whatsappLinks,
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $links
     */
    private function whatsappLinksHtml(array $links): ?HtmlString
    {
        if ($links === []) {
            return null;
        }

        $items = [];
        foreach ($links as $link) {
            $items[] = '<li style="margin:0.35rem 0;"><strong>'.e($link['name']).'</strong> — '
                .'<a href="'.e($link['url']).'" target="_blank" rel="noopener noreferrer" '
                .'style="color:#128c7e;font-weight:600;text-decoration:underline;">Ouvrir WhatsApp</a></li>';
        }

        return new HtmlString(
            '<p style="margin:0 0 0.4rem;font-weight:600;">Liens WhatsApp à ouvrir :</p>'
            .'<ul style="margin:0;padding-left:1.1rem;">'.implode('', $items).'</ul>'
        );
    }

    /**
     * @return list<int>
     */
    private function collectDepartmentIds(GuestInfoForm $form): array
    {
        $ids = [];
        $form->loadMissing('sections.fields');

        foreach ($form->sections as $section) {
            foreach ($section->department_ids ?? [] as $id) {
                $ids[] = (int) $id;
            }
            foreach ($section->fields as $field) {
                foreach ($field->effectiveDepartmentIds() as $id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Collecte tous les e-mails des responsables (contacts + comptes liés).
     *
     * @return list<string>
     */
    private function departmentEmailRecipients(ChurchDepartment $department): array
    {
        $department->loadMissing(['manager', 'managers.user']);

        $emails = [];
        if (filled($department->contact_email)) {
            $emails[] = mb_strtolower(trim((string) $department->contact_email));
        }
        if ($department->manager && filled($department->manager->email)) {
            $emails[] = mb_strtolower(trim((string) $department->manager->email));
        }

        foreach ($department->managers as $manager) {
            if (filled($manager->email)) {
                $emails[] = mb_strtolower(trim((string) $manager->email));
            }
            if ($manager->user && filled($manager->user->email)) {
                $emails[] = mb_strtolower(trim((string) $manager->user->email));
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    /**
     * Collecte tous les téléphones des responsables pour SMS / WhatsApp.
     *
     * @return list<string>
     */
    private function departmentPhoneRecipients(ChurchDepartment $department): array
    {
        $department->loadMissing('managers');

        $phones = [];
        if (filled($department->contact_phone)) {
            $phones[] = trim((string) $department->contact_phone);
        }

        foreach ($department->managers as $manager) {
            if (filled($manager->phone)) {
                $phones[] = trim((string) $manager->phone);
            }
        }

        $unique = [];
        $seen = [];
        foreach ($phones as $phone) {
            $digits = $this->normalizePhoneDigits($phone);
            $key = $digits !== '' ? $digits : mb_strtolower($phone);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $phone;
        }

        return $unique;
    }

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

    private function passwordCacheKey(int $formId): string
    {
        return 'guest_info_form_plain_password_'.$formId;
    }
}
