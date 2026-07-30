<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\ProtocolReporter;
use App\Models\WorshipServiceReport;
use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API publique : saisie des stats de culte par l'équipe protocole (OTP requis).
 */
final class PublicWorshipServiceReportController extends Controller
{
    public function __construct(
        private readonly PhoneOtpService $otpService,
    ) {}

    /**
     * Métadonnées du formulaire (types de culte).
     *
     * @return JsonResponse `{ data: { service_types: [...] } }`
     */
    public function meta(): JsonResponse
    {
        $types = [];
        foreach (WorshipServiceReport::typeOptions() as $value => $label) {
            $types[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return response()->json([
            'data' => [
                'service_types' => $types,
            ],
        ]);
    }

    /**
     * Vérifie qu'un numéro est enregistré côté protocole et renvoie le nom.
     *
     * @return JsonResponse `{ data: { ok, name, phone } }`
     */
    public function lookupPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $reporter = $this->requireActiveReporter($validated['phone']);

        return response()->json([
            'data' => [
                'ok' => true,
                'name' => $reporter->name,
                'phone' => $reporter->phone,
            ],
        ]);
    }

    /**
     * Envoie un OTP SMS si le numéro est autorisé.
     *
     * @return JsonResponse `{ data: { ok, message } }`
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $this->requireActiveReporter($validated['phone']);

        $result = $this->otpService->send(
            $validated['phone'],
            PhoneOtp::PURPOSE_WORSHIP_REPORT,
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
     * Vérifie le code OTP du rapporteur.
     *
     * @return JsonResponse `{ data: { ok, verified, message } }`
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        $this->requireActiveReporter($validated['phone']);

        $this->otpService->verify(
            $validated['phone'],
            PhoneOtp::PURPOSE_WORSHIP_REPORT,
            $validated['otp_code'],
        );

        return response()->json([
            'data' => [
                'ok' => true,
                'verified' => true,
                'message' => 'Numéro vérifié. Vous pouvez envoyer le rapport.',
            ],
        ]);
    }

    /**
     * Enregistre un rapport de présence (OTP vérifié obligatoire).
     *
     * @return JsonResponse `{ data: { ok, id, message } }`
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'service_type' => ['required', 'string', 'in:'.implode(',', array_keys(WorshipServiceReport::typeOptions()))],
            'attendees_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'report_text' => ['required', 'string', 'max:5000'],
            'submitted_by' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        $reporter = $this->requireActiveReporter($validated['phone']);

        if (! $this->otpService->hasVerifiedRecently(
            $validated['phone'],
            PhoneOtp::PURPOSE_WORSHIP_REPORT,
            60,
        )) {
            $this->otpService->verify(
                $validated['phone'],
                PhoneOtp::PURPOSE_WORSHIP_REPORT,
                $validated['otp_code'],
            );
        }

        $normalizedPhone = $this->otpService->normalizePhone($validated['phone']);
        $submittedBy = isset($validated['submitted_by']) && trim((string) $validated['submitted_by']) !== ''
            ? trim((string) $validated['submitted_by'])
            : $reporter->name;

        $report = WorshipServiceReport::query()->create([
            'service_date' => $validated['service_date'],
            'service_type' => $validated['service_type'],
            'attendees_count' => (int) $validated['attendees_count'],
            'report_text' => trim($validated['report_text']),
            'submitted_by' => $submittedBy,
            'phone' => $normalizedPhone,
        ]);

        return response()->json([
            'data' => [
                'ok' => true,
                'id' => $report->id,
                'message' => 'Merci. Le rapport de culte a été transmis à l’administration.',
            ],
        ], 201);
    }

    /**
     * Garantit qu'un rapporteur actif existe pour ce numéro.
     */
    private function requireActiveReporter(string $phone): ProtocolReporter
    {
        $normalized = $this->otpService->normalizePhone($phone);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'phone' => 'Numéro de téléphone invalide.',
            ]);
        }

        $reporter = ProtocolReporter::findActiveByPhone($normalized);

        if ($reporter === null) {
            throw ValidationException::withMessages([
                'phone' => 'Ce numéro n’est pas autorisé. Contactez l’administration pour l’enregistrer.',
            ]);
        }

        return $reporter;
    }
}
