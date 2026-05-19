<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\SiteInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enregistre les demandes issues des pages publiques (prière, rendez-vous).
 */
final class PublicSiteInquiryController extends Controller
{
    /**
     * Persiste une requête de prière ou une demande de rendez-vous.
     *
     * @param  Request  $request  Corps : `kind`, `name`, `message`, (`email`, `phone`, `preferred_at`).
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
        ]);

        SiteInquiry::query()->create([
            'kind' => $validated['kind'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'preferred_at' => isset($validated['preferred_at'])
                ? $validated['preferred_at']
                : null,
        ]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
