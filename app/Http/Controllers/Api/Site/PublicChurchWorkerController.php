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
                'education_levels' => [
                    'Primaire',
                    'Secondaire',
                    'Graduat',
                    'Licence',
                    'Master',
                    'Doctorat',
                    'Autre',
                ],
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
