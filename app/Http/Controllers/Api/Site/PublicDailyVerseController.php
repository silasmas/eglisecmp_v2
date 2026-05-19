<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\DailyVerse;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expose le verset du jour actuellement dans sa fenêtre de 24 h.
 */
class PublicDailyVerseController extends Controller
{
    /**
     * Retourne le verset actif ou `data: null` si aucune plage ne correspond.
     *
     * @param  Request  $request  Requête (paramètre `locale` optionnel).
     * @return JsonResponse Objet verset ou null.
     */
    public function show(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $verse = DailyVerse::query()
            ->where('is_active', true)
            ->where('publish_at', '<=', now())
            ->where('visible_until', '>', now())
            ->orderByDesc('publish_at')
            ->first();

        if ($verse === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => SitePublicSerializer::dailyVerseToPublicArray($verse, $locale, $fallback),
        ]);
    }
}
