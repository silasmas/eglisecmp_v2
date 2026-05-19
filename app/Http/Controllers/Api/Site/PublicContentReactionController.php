<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ContentReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Lecture et bascule des réactions publiques (visiteur identifié par un UUID stocké côté client).
 */
class PublicContentReactionController extends Controller
{
    /**
     * Retourne les libellés des réactions autorisées (pour affichage SPA).
     *
     * @return JsonResponse Objet `reactionKeys` (clé => libellé).
     */
    public function keys(): JsonResponse
    {
        $keys = (array) config('site_public.reaction_keys', []);

        return response()->json(['data' => ['reactionKeys' => $keys]]);
    }

    /**
     * Agrège les comptages et, si `visitor_token` est fourni, les réactions actives du visiteur.
     *
     * @param  Request  $request  Query `keys` (liste séparée par des virgules), `visitor_token` optionnel.
     * @return JsonResponse `counts` et `mine`.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keys' => ['required', 'string', 'max:2000'],
            'visitor_token' => ['nullable', 'uuid'],
        ]);

        $rawKeys = array_filter(array_map('trim', explode(',', $validated['keys'])));
        $keys = array_values(array_filter($rawKeys, static fn (string $k): bool => self::isValidReactableKey($k)));

        if ($keys === []) {
            return response()->json([
                'data' => [
                    'counts' => [],
                    'mine' => [],
                ],
            ]);
        }

        $counts = $this->aggregateCounts($keys);
        $mine = [];

        if (isset($validated['visitor_token']) && is_string($validated['visitor_token'])) {
            $mine = $this->visitorSelections($keys, $validated['visitor_token']);
        }

        return response()->json([
            'data' => [
                'counts' => $counts,
                'mine' => $mine,
            ],
        ]);
    }

    /**
     * Ajoute ou retire une réaction pour le couple (contenu, visiteur, type de réaction).
     *
     * @param  Request  $request  Corps JSON : reactable_key, reaction_key, visitor_token.
     * @return JsonResponse Comptages mis à jour pour la clé et indicateur `active`.
     */
    public function store(Request $request): JsonResponse
    {
        $allowed = array_keys((array) config('site_public.reaction_keys', []));

        $validated = $request->validate([
            'reactable_key' => ['required', 'string', 'max:80', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! self::isValidReactableKey($value)) {
                    $fail('La clé de contenu est invalide.');
                }
            }],
            'reaction_key' => ['required', 'string', 'max:32', Rule::in($allowed)],
            'visitor_token' => ['required', 'uuid'],
        ]);

        $reactableKey = $validated['reactable_key'];
        $reactionKey = $validated['reaction_key'];
        $visitorToken = $validated['visitor_token'];

        $exists = ContentReaction::query()
            ->where('reactable_key', $reactableKey)
            ->where('reaction_key', $reactionKey)
            ->where('visitor_token', $visitorToken)
            ->exists();

        if ($exists) {
            ContentReaction::query()
                ->where('reactable_key', $reactableKey)
                ->where('reaction_key', $reactionKey)
                ->where('visitor_token', $visitorToken)
                ->delete();
            $active = false;
        } else {
            ContentReaction::query()->create([
                'reactable_key' => $reactableKey,
                'reaction_key' => $reactionKey,
                'visitor_token' => $visitorToken,
            ]);
            $active = true;
        }

        $countsRow = $this->aggregateCounts([$reactableKey]);

        return response()->json([
            'data' => [
                'reactable_key' => $reactableKey,
                'reaction_key' => $reactionKey,
                'active' => $active,
                'counts' => $countsRow[$reactableKey] ?? [],
            ],
        ]);
    }

    /**
     * @param  list<string>  $keys  Liste de clés reactable.
     * @return array<string, array<string, int>>
     */
    private function aggregateCounts(array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            $out[$key] = [];
        }

        $rows = ContentReaction::query()
            ->whereIn('reactable_key', $keys)
            ->select(['reactable_key', 'reaction_key', DB::raw('count(*) as aggregate')])
            ->groupBy('reactable_key', 'reaction_key')
            ->get();

        foreach ($rows as $row) {
            $rk = (string) $row->reactable_key;
            $out[$rk][(string) $row->reaction_key] = (int) $row->aggregate;
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys  Clés reactable.
     * @return array<string, list<string>> Réactions choisies par le visiteur pour chaque contenu.
     */
    private function visitorSelections(array $keys, string $visitorToken): array
    {
        $out = [];

        foreach ($keys as $key) {
            $out[$key] = [];
        }

        $rows = ContentReaction::query()
            ->whereIn('reactable_key', $keys)
            ->where('visitor_token', $visitorToken)
            ->get(['reactable_key', 'reaction_key']);

        foreach ($rows as $row) {
            $rk = (string) $row->reactable_key;
            $out[$rk][] = (string) $row->reaction_key;
        }

        return $out;
    }

    /**
     * Valide le format « type:id » (identifiant numérique).
     */
    private static function isValidReactableKey(string $key): bool
    {
        return (bool) preg_match('/^[a-z0-9_]+:\d+$/', $key);
    }
}
