<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Minister;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API publique : liste des pasteurs pour la page Leadership.
 */
final class PublicMinisterController extends Controller
{
    /**
     * Retourne tous les pasteurs actifs avec photo, bio et fonction.
     *
     * @param  Request  $request  Requête HTTP (locale optionnelle).
     * @return JsonResponse `{ data: ministers[] }`
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $rows = Minister::query()
            ->where('is_active', true)
            ->orderBy('fullname')
            ->get()
            ->map(static function (Minister $minister) use ($locale, $fallback): array {
                $role = SitePublicSerializer::text($minister->type ?? '', $locale, $fallback);

                return [
                    'id' => $minister->id,
                    'fullname' => SitePublicSerializer::text($minister->fullname, $locale, $fallback),
                    'image_url' => SitePublicSerializer::imageUrl($minister->image_url, $locale, $fallback),
                    'bio' => SitePublicSerializer::text($minister->bio ?? '', $locale, $fallback),
                    'role' => $role !== '' ? $role : 'Pasteur',
                    'is_titular' => (bool) $minister->is_titular,
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }
}
