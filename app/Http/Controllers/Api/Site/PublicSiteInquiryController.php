<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Notifications\SiteAppointmentSubmittedNotification;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Enregistre les demandes issues des pages publiques (prière, rendez-vous).
 */
final class PublicSiteInquiryController extends Controller
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
    ) {}

    /**
     * Persiste une requête de prière ou une demande de rendez-vous.
     *
     * @param  Request  $request  Corps : `kind`, `name`, `message`, (`email`, `phone`, `preferred_at`, `minister_id`).
     * @return JsonResponse Objet `{ data: { ok: true } }` si succès.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => 'required|string|in:'.SiteInquiry::KIND_PRAYER.','.SiteInquiry::KIND_APPOINTMENT,
            'name' => 'required|string|max:190',
            'email' => 'nullable|string|email|max:190',
            'phone' => 'nullable|string|max:190',
            'message' => 'required|string|max:12000',
            'preferred_at' => 'nullable|date',
            'minister_id' => 'nullable|integer|min:1',
        ]);

        if ($validated['kind'] === SiteInquiry::KIND_APPOINTMENT) {
            $request->validate([
                'minister_id' => 'required|integer|min:1',
                'preferred_at' => 'required|date',
                'phone' => 'required|string|max:190',
            ]);
        }

        $preferredAt = isset($validated['preferred_at'])
            ? Carbon::parse($validated['preferred_at'])
            : null;

        if (
            $validated['kind'] === SiteInquiry::KIND_APPOINTMENT
            && $preferredAt !== null
            && isset($validated['minister_id'])
        ) {
            if (! $this->availability->slotIsAvailable((int) $validated['minister_id'], $preferredAt)) {
                return response()->json([
                    'message' => 'Ce créneau n’est plus disponible. Choisissez un autre horaire.',
                ], 422);
            }
        }

        $bureauId = null;

        if (
            $validated['kind'] === SiteInquiry::KIND_APPOINTMENT
            && $preferredAt !== null
            && isset($validated['minister_id'])
        ) {
            $bureauId = $this->availability->resolveBureauForSlot((int) $validated['minister_id'], $preferredAt);
        }

        $inquiry = SiteInquiry::query()->create([
            'kind' => $validated['kind'],
            'minister_id' => $validated['minister_id'] ?? null,
            'bureau_id' => $bureauId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'preferred_at' => $preferredAt,
            'appointment_status' => $validated['kind'] === SiteInquiry::KIND_APPOINTMENT
                ? SiteInquiry::STATUS_PENDING
                : SiteInquiry::STATUS_PENDING,
        ]);

        if ($inquiry->kind === SiteInquiry::KIND_APPOINTMENT) {
            $this->notifyAdmins($inquiry);
        }

        return response()->json(['data' => ['ok' => true]]);
    }

    /**
     * Notifie les comptes admin / notifiables d’une nouvelle demande de RDV.
     */
    private function notifyAdmins(SiteInquiry $inquiry): void
    {
        $recipients = User::query()
            ->where('notifiable', true)
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new SiteAppointmentSubmittedNotification($inquiry));
        }
    }
}
