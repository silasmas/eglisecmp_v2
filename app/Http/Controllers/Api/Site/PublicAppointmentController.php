<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Services\AppointmentAvailabilityService;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * API publique : pasteurs disponibles, dates et créneaux pour les rendez-vous.
 */
final class PublicAppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
    ) {}

    /**
     * Liste les pasteurs avec horaires de réception configurés.
     *
     * @param  Request  $request  Requête HTTP (locale optionnelle).
     * @return JsonResponse `{ data: ministers[] }`
     */
    public function ministers(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        return response()->json([
            'data' => $this->availability->ministersForBooking($locale, $fallback),
        ]);
    }

    /**
     * Dates disponibles pour un pasteur (prochains 60 jours).
     *
     * @param  Request  $request  Query `minister_id` requis.
     * @return JsonResponse `{ data: { dates: string[] } }`
     */
    public function dates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minister_id' => 'required|integer|min:1',
        ]);

        return response()->json([
            'data' => [
                'dates' => $this->availability->availableDatesForMinister((int) $validated['minister_id']),
            ],
        ]);
    }

    /**
     * Créneaux disponibles pour un pasteur à une date.
     *
     * @param  Request  $request  Query `minister_id`, `date` (Y-m-d).
     * @return JsonResponse `{ data: { slots: array } }`
     */
    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minister_id' => 'required|integer|min:1',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $slots = $this->availability->slotsForMinisterOnDate((int) $validated['minister_id'], $date);

        return response()->json([
            'data' => [
                'slots' => $slots->all(),
            ],
        ]);
    }
}
