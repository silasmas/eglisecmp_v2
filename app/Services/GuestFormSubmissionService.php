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
use Illuminate\Support\Str;

/**
 * Enregistre une soumission et notifie les départements concernés.
 */
final class GuestFormSubmissionService
{
    /**
     * Crée la soumission, marque le pasteur, envoie les mails départements.
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

        $pastor->update([
            'form_submitted_at' => now(),
        ]);

        $this->notifyDepartments($submission, $form);

        return $submission;
    }

    /**
     * Notifie chaque département lié aux champs du formulaire.
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function notifyDepartments(
        GuestInfoSubmission $submission,
        GuestInfoForm $form,
        ?User $actor = null,
    ): array {
        $departmentIds = $this->collectDepartmentIds($form);
        if ($departmentIds === []) {
            $departmentIds = $form->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        }

        $passwordHint = (string) Cache::get($this->passwordCacheKey($form->id), 'Voir l’administration CMP');
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($departmentIds as $departmentId) {
            $department = ChurchDepartment::query()->with('manager')->find($departmentId);
            if ($department === null) {
                $skipped++;

                continue;
            }

            $filtered = GuestFormAnswerScope::visiblePayloadForDepartment($submission, $departmentId);
            if ($filtered === [] && $form->project?->departments()->where('church_departments.id', $departmentId)->exists() !== true) {
                $skipped++;

                continue;
            }

            $portalUrl = $submission->publicResponsesUrl().'?dept='.$departmentId;
            $recipients = $this->departmentRecipients($department);

            if ($recipients === []) {
                GuestDepartmentNotification::query()->create([
                    'guest_info_submission_id' => $submission->id,
                    'church_department_id' => $departmentId,
                    'channel' => GuestDepartmentNotification::CHANNEL_EMAIL,
                    'recipient' => null,
                    'status' => GuestDepartmentNotification::STATUS_SKIPPED,
                    'meta' => ['reason' => 'no_email'],
                    'sent_by_user_id' => $actor?->id,
                    'sent_at' => now(),
                ]);
                $skipped++;

                continue;
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
                        'church_department_id' => $departmentId,
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
                        'church_department_id' => $departmentId,
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
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
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
            // Pas d’historique d’envoi (ancien flux) : créer une ligne d’accusé.
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
     * @return list<string>
     */
    private function departmentRecipients(ChurchDepartment $department): array
    {
        $emails = [];
        if (filled($department->contact_email)) {
            $emails[] = (string) $department->contact_email;
        }
        if ($department->manager && filled($department->manager->email)) {
            $emails[] = (string) $department->manager->email;
        }

        return array_values(array_unique($emails));
    }

    private function passwordCacheKey(int $formId): string
    {
        return 'guest_info_form_plain_password_'.$formId;
    }
}
