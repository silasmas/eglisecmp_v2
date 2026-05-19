<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Posts mis en avant sur la page d'accueil (programmation Filament).
 */
class PublicFeaturedPostController extends Controller
{
    /**
     * Retourne les publications à la une, ordonnées pour la SPA.
     *
     * @param  Request  $request  Requête (`limit` optionnel, défaut 6).
     * @return JsonResponse Tableau dans `data`.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $limit = min(max((int) $request->query('limit', 6), 1), 24);

        $rows = Post::query()
            ->where('is_active', true)
            ->featuredOnHomeNow()
            ->with('minister')
            ->orderBy('featured_sort_order')
            ->orderByDesc('date_publication')
            ->limit($limit)
            ->get();

        $payload = $rows->map(
            static fn (Post $post): array => SitePublicSerializer::featuredPostToHomeCardArray($post, $locale, $fallback)
        )->values()->all();

        return response()->json(['data' => $payload]);
    }
}
