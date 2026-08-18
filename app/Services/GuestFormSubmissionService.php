<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\GuestFormDepartmentMail;
use App\Models\ChurchDepartment;
use App\Models\GuestInfoForm;
use App\Models\GuestInfoFormField;
use App\Models\GuestInfoSubmission;
use App\Models\GuestPastor;
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
     */
    public function notifyDepartments(GuestInfoSubmission $submission, GuestInfoForm $form): void
    {
        $departmentIds = $this->collectDepartmentIds($form);
        if ($departmentIds === []) {
            $departmentIds = $form->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        }

        $passwordHint = (string) Cache::get($this->passwordCacheKey($form->id), 'Voir l’administration CMP');

        foreach ($departmentIds as $departmentId) {
            $department = ChurchDepartment::query()->with('manager')->find($departmentId);
            if ($department === null) {
                continue;
            }

            $filtered = GuestFormAnswerScope::visiblePayloadForDepartment($submission, $departmentId);
            if ($filtered === [] && $form->project?->departments()->where('church_departments.id', $departmentId)->exists() !== true) {
                continue;
            }

            $portalUrl = $submission->publicResponsesUrl().'?dept='.$departmentId;
            $recipients = $this->departmentRecipients($department);

            foreach ($recipients as $email) {
                Mail::to($email)->send(new GuestFormDepartmentMail(
                    $submission,
                    $department,
                    $portalUrl,
                    $passwordHint,
                ));
            }
        }
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
