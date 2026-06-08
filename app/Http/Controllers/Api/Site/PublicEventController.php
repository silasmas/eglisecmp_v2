<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\EventPublicFilter;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expose la liste des événements actifs pour le site public (SPA).
 */
class PublicEventController extends Controller
{
    /**
     * Retourne une liste JSON d'événements destinée au front React.
     *
     * @param  Request  $request  Requête (paramètres optionnels : `limit`, `locale`).
     * @return JsonResponse Tableau d'objets événement sérialisés.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $limit = min(max((int) $request->query('limit', 20), 1), 100);
        $scope = (string) $request->query('scope', 'highlight');

        $rows = Event::query()
            ->where('is_active', true)
            ->orderByDesc('date_debut')
            ->orderByDesc('youtube_published_at')
            ->orderByDesc('id')
            ->limit(min($limit * 4, 200))
            ->get();

        $payload = $rows
            ->map(static fn (Event $event): array => SitePublicSerializer::eventToPublicArray($event, $locale, $fallback))
            ->filter(static fn (array $row): bool => ($row['hasPoster'] ?? false) === true)
            ->all();

        if ($scope === 'highlight') {
            $payload = EventPublicFilter::onlyHighlight($payload);
        }

        usort($payload, static function (array $a, array $b): int {
            $aFeatured = ($a['featured'] ?? false) === true ? 1 : 0;
            $bFeatured = ($b['featured'] ?? false) === true ? 1 : 0;
            if ($aFeatured !== $bFeatured) {
                return $bFeatured <=> $aFeatured;
            }

            return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        });

        $payload = array_slice($payload, 0, $limit);

        return response()->json(['data' => array_values($payload)]);
    }

    /**
     * Retourne l'événement mis en avant programmé (modale d'accueil).
     *
     * @param  Request  $request  Requête (`locale` optionnel).
     * @return JsonResponse Objet événement ou `null` dans `data`.
     */
    public function spotlight(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();

        $event = Event::query()
            ->featuredSpotlightNow()
            ->orderBy('date_debut')
            ->first();

        if ($event === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => SitePublicSerializer::eventToPublicArray($event, $locale, $fallback),
        ]);
    }
}
