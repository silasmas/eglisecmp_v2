<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ChildPresentation;
use App\Models\PhoneOtp;
use App\Models\PresentedChild;
use App\Services\ChildPresentationAvailabilityService;
use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * API publique : présentation d'enfants (dates, OTP, soumission).
 */
final class PublicChildPresentationController extends Controller
{
    public function __construct(
        private readonly ChildPresentationAvailabilityService $availability,
        private readonly PhoneOtpService $otpService,
    ) {}

    /**
     * Métadonnées publiques (dates dispo, âge ECODIM, consignes).
     *
     * @return JsonResponse `{ data: {...} }`
     */
    public function meta(): JsonResponse
    {
        $ecodimAge = (int) config('child_presentation.ecodim_entry_age_years', 3);

        return response()->json([
            'data' => [
                'dates' => $this->availability->upcomingDates(),
                'ecodim_entry_age_years' => $ecodimAge,
                'max_document_mb' => (int) config('child_presentation.max_document_mb', 5),
                'requirements' => [
                    'La présentation des enfants a lieu uniquement les 2e et 4e dimanches du mois.',
                    'Prévoir l\'acte de naissance de chaque enfant (pièce jointe).',
                    'Prévoir une pièce d\'identité d\'au moins un parent (pièce jointe).',
                    'Être présent au début du culte le jour de la présentation.',
                    "L'ECODIM accueille les enfants à partir de {$ecodimAge} ans.",
                ],
            ],
        ]);
    }

    /**
     * Calcule le message ECODIM pour un âge saisi.
     *
     * @return JsonResponse `{ data: { eligible, message, months_remaining } }`
     */
    public function ecodimHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'age_years' => ['required', 'integer', 'min:0', 'max:17'],
            'age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
        ]);

        return response()->json([
            'data' => $this->availability->ecodimMessage(
                (int) $validated['age_years'],
                (int) ($validated['age_months'] ?? 0),
            ),
        ]);
    }

    /**
     * Envoie un code OTP SMS au numéro fourni.
     *
     * @return JsonResponse `{ data: { ok, message } }`
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $result = $this->otpService->send(
            $validated['phone'],
            PhoneOtp::PURPOSE_CHILD_PRESENTATION,
        );

        if (! $result['sms']->success) {
            throw ValidationException::withMessages([
                'phone' => $result['sms']->error ?? 'Impossible d\'envoyer le SMS de vérification.',
            ]);
        }

        return response()->json([
            'data' => [
                'ok' => true,
                'message' => 'Un code de vérification a été envoyé par SMS.',
            ],
        ]);
    }

    /**
     * Vérifie le code OTP saisi par le parent.
     *
     * @return JsonResponse `{ data: { ok, verified: true } }`
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        $this->otpService->verify(
            $validated['phone'],
            PhoneOtp::PURPOSE_CHILD_PRESENTATION,
            $validated['otp_code'],
        );

        return response()->json([
            'data' => [
                'ok' => true,
                'verified' => true,
                'message' => 'Numéro vérifié avec succès.',
            ],
        ]);
    }

    /**
     * Enregistre une demande de présentation (multipart).
     *
     * @return JsonResponse `{ data: { ok, id, message } }`
     */
    public function store(Request $request): JsonResponse
    {
        $maxMb = max(1, (int) config('child_presentation.max_document_mb', 5));

        $validated = $request->validate([
            'children_count' => ['required', 'integer', 'min:1', 'max:10'],
            'parent_names' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'otp_code' => ['required', 'string', 'max:12'],
            'presentation_date' => ['required', 'date_format:Y-m-d'],
            'children' => ['required', 'array', 'min:1'],
            'children.*.full_name' => ['required', 'string', 'max:255'],
            'children.*.gender' => ['required', 'string', 'in:male,female'],
            'children.*.age_years' => ['required', 'integer', 'min:0', 'max:17'],
            'children.*.age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'birth_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', "max:{$maxMb}000"],
            'parent_id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', "max:{$maxMb}000"],
        ]);

        $childrenCount = (int) $validated['children_count'];
        $children = $validated['children'];

        if (count($children) !== $childrenCount) {
            throw ValidationException::withMessages([
                'children' => 'Le nombre d\'enfants doit correspondre aux fiches renseignées.',
            ]);
        }

        if (! $this->availability->isValidPresentationDate($validated['presentation_date'])) {
            throw ValidationException::withMessages([
                'presentation_date' => 'Choisissez un 2e ou 4e dimanche parmi les dates proposées.',
            ]);
        }

        $normalizedPhone = $this->otpService->normalizePhone($validated['phone']);

        // Accepte un OTP déjà validé récemment, sinon vérifie le code fourni.
        if (! $this->otpService->hasVerifiedRecently(
            $validated['phone'],
            PhoneOtp::PURPOSE_CHILD_PRESENTATION,
            60,
        )) {
            $this->otpService->verify(
                $validated['phone'],
                PhoneOtp::PURPOSE_CHILD_PRESENTATION,
                $validated['otp_code'],
            );
        }

        $presentationId = DB::transaction(function () use ($request, $validated, $childrenCount, $children, $normalizedPhone): int {
            $birthPath = $request->file('birth_certificate')?->store('child-presentations/birth-certificates', 'public');
            $idPath = $request->file('parent_id_document')?->store('child-presentations/parent-ids', 'public');

            $presentation = ChildPresentation::query()->create([
                'children_count' => $childrenCount,
                'parent_names' => trim($validated['parent_names']),
                'phone' => $normalizedPhone,
                'phone_verified' => true,
                'birth_certificate_path' => $birthPath,
                'parent_id_document_path' => $idPath,
                'presentation_date' => $validated['presentation_date'],
                'status' => ChildPresentation::STATUS_PENDING,
            ]);

            foreach ($children as $child) {
                PresentedChild::query()->create([
                    'child_presentation_id' => $presentation->id,
                    'full_name' => trim((string) $child['full_name']),
                    'gender' => (string) $child['gender'],
                    'age_years' => (int) $child['age_years'],
                    'age_months' => (int) ($child['age_months'] ?? 0),
                ]);
            }

            return $presentation->id;
        });

        return response()->json([
            'data' => [
                'ok' => true,
                'id' => $presentationId,
                'message' => 'Votre demande a été envoyée. Vous recevrez un SMS après validation par l\'administration.',
            ],
        ], 201);
    }
}
