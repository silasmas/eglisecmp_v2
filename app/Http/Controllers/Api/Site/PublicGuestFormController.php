<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\GuestInfoForm;
use App\Models\GuestInfoFormField;
use App\Models\GuestInfoSubmission;
use App\Models\GuestPastor;
use App\Services\GuestFormSubmissionService;
use App\Support\GuestFormAnswerScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * API publique : formulaire pasteur invité + portail réponses départements.
 */
final class PublicGuestFormController extends Controller
{
    /**
     * Charge le formulaire lié au token d’invitation du pasteur.
     */
    public function show(string $token): JsonResponse
    {
        $pastor = GuestPastor::query()
            ->with(['project.form.sections.fields'])
            ->where('invite_token', $token)
            ->first();

        if ($pastor === null) {
            return response()->json(['message' => 'Invitation introuvable.'], 404);
        }

        $form = $pastor->project?->form;
        if ($form === null || ! $form->isCurrentlyVisible()) {
            return response()->json(['message' => 'Formulaire indisponible ou hors période.'], 403);
        }

        if ($pastor->form_opened_at === null) {
            $pastor->update(['form_opened_at' => now()]);
        }

        $alreadySubmitted = $pastor->form_submitted_at !== null
            || GuestInfoSubmission::query()
                ->where('guest_pastor_id', $pastor->id)
                ->where('form_id', $form->id)
                ->exists();

        return response()->json([
            'pastor' => [
                'full_name' => $pastor->full_name,
                'church_name' => $pastor->church_name,
                'photo_url' => $pastor->photoPublicUrl(),
                'arrival_at' => $pastor->arrival_at?->toIso8601String(),
                'ministry_at' => $pastor->ministry_at?->toIso8601String(),
            ],
            'project' => [
                'title' => $pastor->project?->title,
            ],
            'form' => $this->serializeForm($form),
            'already_submitted' => $alreadySubmitted,
            'headline' => 'Formulaire de préparation pour mieux s’occuper du pasteur '.$pastor->full_name,
        ]);
    }

    /**
     * Enregistre les réponses du pasteur invité.
     */
    public function submit(Request $request, string $token, GuestFormSubmissionService $service): JsonResponse
    {
        $pastor = GuestPastor::query()
            ->with(['project.form.sections.fields'])
            ->where('invite_token', $token)
            ->first();

        if ($pastor === null) {
            return response()->json(['message' => 'Invitation introuvable.'], 404);
        }

        $form = $pastor->project?->form;
        if ($form === null || ! $form->isCurrentlyVisible()) {
            return response()->json(['message' => 'Formulaire indisponible ou hors période.'], 403);
        }

        if ($pastor->form_submitted_at !== null) {
            return response()->json(['message' => 'Ce formulaire a déjà été envoyé.'], 422);
        }

        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            return response()->json(['message' => 'Réponses invalides.'], 422);
        }

        $errors = $this->validateAnswers($form, $answers);
        if ($errors !== []) {
            return response()->json(['message' => 'Validation échouée.', 'errors' => $errors], 422);
        }

        $submission = $service->submit($pastor, $form, $answers);

        return response()->json([
            'message' => 'Merci ! Votre fiche a été enregistrée. Les départements concernés ont été notifiés.',
            'submission_id' => $submission->id,
        ], 201);
    }

    /**
     * Déverrouille le portail réponses (mot de passe + token soumission).
     */
    public function unlockResponses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_token' => ['required', 'string'],
            'password' => ['required', 'string'],
            'department_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $submission = GuestInfoSubmission::query()
            ->with(['guestPastor', 'form.sections.fields', 'form.project'])
            ->where('access_token', $request->string('access_token')->toString())
            ->first();

        if ($submission === null) {
            return response()->json(['message' => 'Soumission introuvable.'], 404);
        }

        $form = $submission->form;
        if ($form === null || ! $form->checkAccessPassword($request->string('password')->toString())) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 403);
        }

        $departmentId = $request->integer('department_id') ?: null;
        $payload = $departmentId !== null
            ? GuestFormAnswerScope::visiblePayloadForDepartment($submission, $departmentId)
            : ($submission->payload ?? []);

        // Sans department_id : ne pas exposer tout le payload publiquement — exiger dept.
        if ($departmentId === null) {
            return response()->json([
                'message' => 'Indiquez department_id pour filtrer les réponses.',
            ], 422);
        }

        $labels = GuestInfoFormField::query()
            ->whereHas('section', fn ($q) => $q->where('form_id', $submission->form_id))
            ->get()
            ->keyBy('key');

        $answers = [];
        foreach ($payload as $key => $value) {
            $field = $labels->get($key);
            $answers[] = [
                'key' => $key,
                'label' => $field?->label ?? $key,
                'type' => $field?->type ?? 'text',
                'value' => $value,
            ];
        }

        return response()->json([
            'pastor' => [
                'full_name' => $submission->guestPastor?->full_name,
                'church_name' => $submission->guestPastor?->church_name,
                'photo_url' => $submission->guestPastor?->photoPublicUrl(),
            ],
            'project_title' => $form->project?->title,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'answers' => $answers,
            'acknowledgment' => app(GuestFormSubmissionService::class)->departmentAckStatus($submission, $departmentId),
            'department_id' => $departmentId,
        ]);
    }

    /**
     * Accusé de réception département sur le portail public.
     */
    public function acknowledgeResponses(Request $request, GuestFormSubmissionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_token' => ['required', 'string'],
            'password' => ['required', 'string'],
            'department_id' => ['required', 'integer'],
            'acknowledger_name' => ['nullable', 'string', 'max:120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $submission = GuestInfoSubmission::query()
            ->with('form')
            ->where('access_token', $request->string('access_token')->toString())
            ->first();

        if ($submission === null) {
            return response()->json(['message' => 'Soumission introuvable.'], 404);
        }

        $form = $submission->form;
        if ($form === null || ! $form->checkAccessPassword($request->string('password')->toString())) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 403);
        }

        $result = $service->acknowledgeDepartment(
            $submission,
            $request->integer('department_id'),
            $request->string('acknowledger_name')->toString() ?: null,
        );

        return response()->json($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeForm(GuestInfoForm $form): array
    {
        $design = $form->design ?? [];
        $banner = $design['banner_path'] ?? null;
        if (is_string($banner) && $banner !== '' && ! str_starts_with($banner, 'http')) {
            $banner = Storage::disk('public')->url($banner);
        }

        return [
            'id' => $form->id,
            'title' => $form->title,
            'layout_mode' => $form->layout_mode ?: GuestInfoForm::LAYOUT_SINGLE,
            'intro_html' => $form->intro_html,
            'cmp_info_html' => $form->cmp_info_html,
            'design' => [
                'banner_url' => $banner,
                'primary_color' => $design['primary_color'] ?? '#7b1d3e',
                'accent_color' => $design['accent_color'] ?? '#ea7e2d',
                'radius' => (int) ($design['radius'] ?? 16),
            ],
            'sections' => $form->sections->map(function ($section): array {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'fields' => $section->fields->map(function (GuestInfoFormField $field): array {
                        return [
                            'key' => $field->key,
                            'label' => $field->label,
                            'type' => $field->type,
                            'required' => $field->required,
                            'help_text' => $field->help_text,
                            'options' => $field->options,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, string>
     */
    private function validateAnswers(GuestInfoForm $form, array $answers): array
    {
        $errors = [];
        foreach ($form->sections as $section) {
            foreach ($section->fields as $field) {
                if (! $field->required) {
                    continue;
                }
                $value = $answers[$field->key] ?? null;
                $empty = $value === null
                    || $value === ''
                    || (is_array($value) && $value === []);
                if ($empty) {
                    $errors[$field->key] = 'Ce champ est obligatoire.';
                }
            }
        }

        return $errors;
    }
}
