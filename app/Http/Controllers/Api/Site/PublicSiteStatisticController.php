<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\SiteStatistic;
use Illuminate\Http\JsonResponse;

/**
 * Chiffres clés (« En chiffres ») pour la page d’accueil SPA.
 */
final class PublicSiteStatisticController extends Controller
{
    /**
     * Liste les lignes actives, triées par `sort_order`.
     *
     * @return JsonResponse Enveloppe `data`: tableau `{ icon_key, label, value, suffix }`.
     */
    public function index(): JsonResponse
    {
        $rows = SiteStatistic::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (SiteStatistic $row): array => [
                'icon_key' => (string) $row->icon_key,
                'label' => (string) $row->label,
                'value' => (int) $row->numeric_value,
                'suffix' => $row->suffix !== null ? (string) $row->suffix : '',
            ])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }
}
