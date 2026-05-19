<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\SitePublicSerializer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expose les publications actives sous forme de « sermons » pour la SPA (pagination + onglets).
 */
class PublicPostController extends Controller
{
    /**
     * Liste paginée des messages pour la page Enseignements.
     *
     * @param  Request  $request  Query : `tab`, `event_id`, `page`, `per_page`, `search`, `locale`.
     * @return JsonResponse Données + métadonnées de pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $tab = is_string($request->query('tab')) ? strtolower(trim($request->query('tab'))) : 'sermons';
        $perPage = min(max((int) $request->query('per_page', 12), 1), 48);
        $page = max((int) $request->query('page', 1), 1);
        $eventIdFilter = null;
        $searchToken = $this->normalizeSearchToken($request);

        if ($tab === 'playlists') {
            $eventIdRaw = $request->query('event_id');

            if (is_string($eventIdRaw) || is_int($eventIdRaw)) {
                $digits = preg_replace('/\D/', '', (string) $eventIdRaw);
                $eventIdFilter = ($digits !== '' && $digits !== '0') ? (int) $digits : null;
            }
        }

        $query = Post::query()
            ->where('is_active', true)
            ->with(['minister', 'event'])
            ->orderByDesc('date_publication')
            ->orderByDesc('id');

        $this->applyTabFilter($query, $tab);

        if ($eventIdFilter !== null) {
            $query->where('event_id', $eventIdFilter);
        }

        if ($searchToken !== null) {
            $this->applyPostsSearchFilter($query, $searchToken);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $items = $paginator->getCollection()->map(
            static fn (Post $post): array => SitePublicSerializer::postToSermonArray($post, $locale, $fallback)
        )->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'tab' => $tab,
                'search' => $searchToken,
            ],
        ]);
    }

    /**
     * Détail d’un message pour la page de lecture (/teachings/message/:id).
     *
     * @param  Request  $request  Requête (paramètre query `locale` optionnel).
     * @param  Post  $post  Publication résolue par la route `{post}`.
     * @return JsonResponse Objet `Sermon` dans la clé `data`.
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        if (! $post->is_active) {
            abort(404);
        }

        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $post->loadMissing(['minister', 'event']);

        return response()->json([
            'data' => SitePublicSerializer::postToSermonArray($post, $locale, $fallback),
        ]);
    }

    /**
     * Réduit une entrée recherche brute à une valeur SQL LIKE sûre (longueur limitée).
     *
     * @param  Request  $request  Requête entrante.
     * @return string|null Jeton trimmed ou null si absent.
     */
    private function normalizeSearchToken(Request $request): ?string
    {
        $searchRaw = $request->query('search');

        if (! is_string($searchRaw)) {
            return null;
        }

        $token = trim($searchRaw);

        if ($token === '') {
            return null;
        }

        return mb_substr($token, 0, 120);
    }

    /**
     * Applique un filtre titre / corps / ministre selon une chaîne de recherche libre.
     *
     * @param  Builder<Post>  $query  Requête en cours de construction.
     * @param  string  $searchToken  Texte utilisateur déjà sanitized (longueur max).
     */
    private function applyPostsSearchFilter(Builder $query, string $searchToken): void
    {
        $like = '%'.addcslashes($searchToken, '%_\\').'%';

        $query->where(function (Builder $sub) use ($like): void {
            $sub
                ->where('slug', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhere('body', 'like', $like)
                ->orWhere('observation', 'like', $like)
                ->orWhere('author', 'like', $like)
                ->orWhere('link_url', 'like', $like)
                ->orWhere('references', 'like', $like)
                ->orWhereHas('minister', function (Builder $ministerQuery) use ($like): void {
                    $ministerQuery->where('fullname', 'like', $like);
                });
        });
    }

    /**
     * Restreint la requête selon l'onglet actif de la page Enseignements.
     *
     * @param  Builder<Post>  $query  Requête Eloquent.
     * @param  string  $tab  sermons | meditations | playlists.
     */
    private function applyTabFilter(Builder $query, string $tab): void
    {
        if ($tab === 'meditations') {
            $types = (array) config('site_public.teachings_tabs.meditations', [2]);
            $query->whereIn('type', array_map('intval', $types));

            return;
        }

        if ($tab === 'playlists') {
            $query->whereNotNull('event_id');

            return;
        }

        $types = (array) config('site_public.teachings_tabs.sermons', [1, 3]);
        $query->whereIn('type', array_map('intval', $types));
    }
}
