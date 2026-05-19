<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expose les éléments de galerie actifs pour le site public (SPA).
 */
class PublicGalleryController extends Controller
{
    /**
     * Retourne une liste JSON d'images / médias pour la grille galerie du front.
     *
     * @param  Request  $request  Requête (paramètres optionnels : `limit`, `locale`).
     * @return JsonResponse Tableau d'objets compatibles avec le type GalleryItem côté client.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $limit = min(max((int) $request->query('limit', 48), 1), 100);

        $rows = Gallery::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $payload = $rows->map(
            static fn (Gallery $gallery): array => SitePublicSerializer::galleryToPublicArray($gallery, $locale, $fallback)
        )->values()->all();

        return response()->json(['data' => $payload]);
    }
}
