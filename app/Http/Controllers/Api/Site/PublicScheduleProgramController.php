<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ScheduleProgram;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Liste les programmes d'antenne publiés pour le site public.
 */
class PublicScheduleProgramController extends Controller
{
    /**
     * Retourne les programmes actifs, triés, avec filtre optionnel sur le type.
     *
     * @param  Request  $request  Requête (`kind` optionnel : daily, weekly, seminar, live, special).
     * @return JsonResponse Liste dans `data`.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $kind = $request->query('kind');

        $query = ScheduleProgram::query()
            ->where('is_active', true)
            ->with('event')
            ->orderBy('sort_order')
            ->orderBy('id');

        if (is_string($kind) && $kind !== '' && strlen($kind) <= 32) {
            $query->where('kind', $kind);
        }

        $rows = $query->get();

        $payload = $rows->map(
            static fn (ScheduleProgram $program): array => SitePublicSerializer::scheduleProgramToPublicArray($program, $locale, $fallback)
        )->values()->all();

        return response()->json(['data' => $payload]);
    }
}
