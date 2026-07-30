<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\EmailOtp;
use App\Services\EmailOtpService;
use App\Services\PhoneOtpService;
use App\Support\FilamentImageUrl;
use App\Support\KinshasaCommunes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * API publique : inscription ouvrier + consultation badge.
 */
final class PublicChurchWorkerController extends Controller
{
    public function __construct(
        private readonly EmailOtpService $emailOtp,
        private readonly PhoneOtpService $phoneOtp,
    ) {}

    /**
     * Métadonnées du wizard (départements, communes).
     */
    public function meta(): JsonResponse
    {
        $departments = ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ChurchDepartment $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'slug' => $d->slug,
                'description' => $d->description ?? '',
                'color' => $d->color,
            ])
            ->values();

        return response()->json([
            'data' => [
                'departments' => $departments,
                'communes' => KinshasaCommunes::all(),
                'cities' => ['Kinshasa'],
                'genders' => collect(ChurchWorker::genderOptions())
                    ->map(fn (string $label, string $value): array => compact('value', 'label'))
                    ->values(),
                'education_levels' => ChurchWorker::educationLevelOptions(),
            ],
        ]);
    }

    /**
     * Envoie un OTP e-mail pour l'inscription.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $this->emailOtp->send($validated['email'], EmailOtp::PURPOSE_WORKER_REGISTRATION);

        return response()->json([
            'data' => [
                'ok' => true,
                'message' => 'Un code de vérification a été envoyé à votre e-mail.',
            ],
        ]);
    }

    /**
     * Vérifie l'OTP e-mail.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        $this->emailOtp->verify(
            $validated['email'],
            EmailOtp::PURPOSE_WORKER_REGISTRATION,
            $validated['otp_code'],
        );

        return response()->json([
            'data' => [
                'ok' => true,
                'verified' => true,
                'message' => 'E-mail vérifié. Vous pouvez valider votre inscription.',
            ],
        ]);
    }

    /**
     * Charge un dossier ouvrier pour modification publique (jeton d’édition).
     */
    public function showForEdit(string $token): JsonResponse
    {
        $worker = $this->findEditableWorker($token);

        return response()->json([
            'data' => $this->serializeEditableWorker($worker),
        ]);
    }

    /**
     * Envoie un OTP pour valider une modification de dossier.
     */
    public function sendEditOtp(string $token): JsonResponse
    {
        $worker = $this->findEditableWorker($token);
        $email = (string) $worker->email;

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Aucun e-mail n’est associé à ce dossier.',
            ]);
        }

        $this->emailOtp->send($email, EmailOtp::PURPOSE_WORKER_PROFILE_UPDATE);

        return response()->json([
            'data' => [
                'ok' => true,
                'message' => 'Un code de vérification a été envoyé à '.$email.'.',
            ],
        ]);
    }

    /**
     * Vérifie l’OTP avant enregistrement des modifications.
     */
    public function verifyEditOtp(Request $request, string $token): JsonResponse
    {
        $worker = $this->findEditableWorker($token);
        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        $this->emailOtp->verify(
            (string) $worker->email,
            EmailOtp::PURPOSE_WORKER_PROFILE_UPDATE,
            $validated['otp_code'],
        );

        return response()->json([
            'data' => [
                'ok' => true,
                'verified' => true,
                'message' => 'Identité confirmée. Vous pouvez enregistrer vos modifications.',
            ],
        ]);
    }

    /**
     * Met à jour le dossier ouvrier (OTP obligatoire ; photo optionnelle si déjà présente).
     */
    public function updateProfile(Request $request, string $token): JsonResponse
    {
        $worker = $this->findEditableWorker($token);

        $validated = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('church_departments', 'id')->where('is_active', true)],
            'last_name' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'string', Rule::in(array_keys(ChurchWorker::genderOptions()))],
            'birth_date' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:190'],
            'otp_code' => ['required', 'string', 'max:12'],
            'city' => ['required', 'string', 'in:Kinshasa'],
            'commune' => ['required', 'string', Rule::in(KinshasaCommunes::all())],
            'quartier' => ['required', 'string', 'max:120'],
            'avenue' => ['required', 'string', 'max:190'],
            'address_reference' => ['nullable', 'string', 'max:255'],
            'studies' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:120'],
            'profession' => ['nullable', 'string', 'max:190'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'department_role' => ['nullable', 'string', 'max:190'],
            'department_joined_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $email = $this->emailOtp->normalizeEmail($validated['email']);
        if ($email !== $this->emailOtp->normalizeEmail((string) $worker->email)) {
            throw ValidationException::withMessages([
                'email' => 'L’e-mail ne peut pas être modifié via ce lien. Contactez l’administration.',
            ]);
        }

        if (! $this->emailOtp->hasVerifiedRecently($email, EmailOtp::PURPOSE_WORKER_PROFILE_UPDATE, 60)) {
            $this->emailOtp->verify(
                $email,
                EmailOtp::PURPOSE_WORKER_PROFILE_UPDATE,
                $validated['otp_code'],
            );
        }

        $normalizedPhone = $this->phoneOtp->normalizePhone($validated['phone']);
        if ($normalizedPhone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Numéro de téléphone invalide.',
            ]);
        }

        $photoPath = $worker->photo_path;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')?->store('workers/photos', 'public') ?? $photoPath;
        }

        if (blank($photoPath)) {
            throw ValidationException::withMessages([
                'photo' => 'Une photo est obligatoire.',
            ]);
        }

        $worker->fill([
            'department_id' => (int) $validated['department_id'],
            'last_name' => trim($validated['last_name']),
            'first_name' => trim($validated['first_name']),
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'phone' => $normalizedPhone,
            'email' => $email,
            'email_verified_at' => now(),
            'city' => $validated['city'],
            'commune' => $validated['commune'],
            'quartier' => trim($validated['quartier']),
            'avenue' => trim($validated['avenue']),
            'address_reference' => isset($validated['address_reference']) ? trim((string) $validated['address_reference']) : null,
            'studies' => isset($validated['studies']) ? trim((string) $validated['studies']) : null,
            'education_level' => $validated['education_level'] ?? null,
            'profession' => isset($validated['profession']) ? trim((string) $validated['profession']) : null,
            'skills' => isset($validated['skills']) ? trim((string) $validated['skills']) : null,
            'department_role' => isset($validated['department_role']) ? trim((string) $validated['department_role']) : null,
            'department_joined_at' => $validated['department_joined_at'] ?? null,
            'photo_path' => $photoPath,
        ]);
        $worker->save();

        return response()->json([
            'data' => [
                'ok' => true,
                'id' => $worker->id,
                'message' => 'Vos informations ont été mises à jour avec succès.',
            ],
        ]);
    }

    /**
     * Retrouve un ouvrier via un jeton d’édition encore valide.
     */
    private function findEditableWorker(string $token): ChurchWorker
    {
        $worker = ChurchWorker::query()
            ->with('department')
            ->where('edit_token', $token)
            ->first();

        if ($worker === null || ! $worker->hasValidEditToken()) {
            abort(404, 'Lien de modification invalide ou expiré.');
        }

        return $worker;
    }

    /**
     * Sérialise le dossier pour le formulaire public de modification.
     *
     * @return array<string, mixed>
     */
    private function serializeEditableWorker(ChurchWorker $worker): array
    {
        return [
            'editToken' => $worker->edit_token,
            'expiresAt' => $worker->edit_token_expires_at?->toIso8601String(),
            'departmentId' => $worker->department_id,
            'lastName' => $worker->last_name,
            'firstName' => $worker->first_name,
            'gender' => $worker->gender,
            'birthDate' => $worker->birth_date?->format('Y-m-d'),
            'phone' => $worker->phone,
            'email' => (string) $worker->email,
            'city' => $worker->city,
            'commune' => $worker->commune,
            'quartier' => $worker->quartier,
            'avenue' => $worker->avenue,
            'addressReference' => $worker->address_reference ?? '',
            'studies' => $worker->studies ?? '',
            'educationLevel' => $worker->education_level ?? '',
            'profession' => $worker->profession ?? '',
            'skills' => $worker->skills ?? '',
            'departmentRole' => $worker->department_role ?? '',
            'departmentJoinedAt' => $worker->department_joined_at?->format('Y-m-d') ?? '',
            'photoUrl' => FilamentImageUrl::resolve($worker->photo_path) ?? '',
            'status' => $worker->status,
        ];
    }

    /**
     * Enregistre une inscription ouvrier (multipart + photo).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('church_departments', 'id')->where('is_active', true)],
            'last_name' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'string', Rule::in(array_keys(ChurchWorker::genderOptions()))],
            'birth_date' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:190'],
            'otp_code' => ['required', 'string', 'max:12'],
            'city' => ['required', 'string', 'in:Kinshasa'],
            'commune' => ['required', 'string', Rule::in(KinshasaCommunes::all())],
            'quartier' => ['required', 'string', 'max:120'],
            'avenue' => ['required', 'string', 'max:190'],
            'address_reference' => ['nullable', 'string', 'max:255'],
            'studies' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:120'],
            'profession' => ['nullable', 'string', 'max:190'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'department_role' => ['nullable', 'string', 'max:190'],
            'department_joined_at' => ['nullable', 'date'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (! $this->emailOtp->hasVerifiedRecently($validated['email'], EmailOtp::PURPOSE_WORKER_REGISTRATION, 60)) {
            $this->emailOtp->verify(
                $validated['email'],
                EmailOtp::PURPOSE_WORKER_REGISTRATION,
                $validated['otp_code'],
            );
        }

        $normalizedPhone = $this->phoneOtp->normalizePhone($validated['phone']);
        if ($normalizedPhone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Numéro de téléphone invalide.',
            ]);
        }

        $photoPath = $request->file('photo')?->store('workers/photos', 'public');

        $worker = ChurchWorker::query()->create([
            'department_id' => (int) $validated['department_id'],
            'last_name' => trim($validated['last_name']),
            'first_name' => trim($validated['first_name']),
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'phone' => $normalizedPhone,
            'email' => $this->emailOtp->normalizeEmail($validated['email']),
            'email_verified_at' => now(),
            'city' => $validated['city'],
            'commune' => $validated['commune'],
            'quartier' => trim($validated['quartier']),
            'avenue' => trim($validated['avenue']),
            'address_reference' => isset($validated['address_reference']) ? trim((string) $validated['address_reference']) : null,
            'studies' => isset($validated['studies']) ? trim((string) $validated['studies']) : null,
            'education_level' => $validated['education_level'] ?? null,
            'profession' => isset($validated['profession']) ? trim((string) $validated['profession']) : null,
            'skills' => isset($validated['skills']) ? trim((string) $validated['skills']) : null,
            'department_role' => isset($validated['department_role']) ? trim((string) $validated['department_role']) : null,
            'department_joined_at' => $validated['department_joined_at'] ?? null,
            'photo_path' => $photoPath,
            'status' => ChurchWorker::STATUS_PENDING,
            'badge_token' => (string) Str::uuid(),
        ]);

        return response()->json([
            'data' => [
                'ok' => true,
                'id' => $worker->id,
                'message' => 'Inscription envoyée. Un responsable de département validera votre dossier.',
            ],
        ], 201);
    }

    /**
     * Données publiques du badge ouvrier (QR / lien).
     */
    public function badge(string $token): JsonResponse
    {
        $worker = ChurchWorker::query()
            ->with('department')
            ->where('badge_token', $token)
            ->firstOrFail();

        $photoUrl = FilamentImageUrl::resolve($worker->photo_path) ?? '';

        return response()->json([
            'data' => [
                'token' => $worker->badge_token,
                'fullName' => $worker->fullName(),
                'firstName' => $worker->first_name,
                'lastName' => $worker->last_name,
                'gender' => $worker->gender,
                'department' => $worker->department?->name ?? '',
                'departmentColor' => $worker->department?->color ?? '#7b1d3e',
                'departmentRole' => $worker->department_role ?? '',
                'photoUrl' => $photoUrl,
                'status' => $worker->status,
                'badgeValidated' => $worker->hasValidatedBadge(),
                'badgeGenerated' => (bool) $worker->badge_generated,
                'phone' => $worker->status === ChurchWorker::STATUS_APPROVED ? $worker->phone : null,
                'commune' => $worker->commune,
                'city' => $worker->city,
            ],
        ]);
    }
}
