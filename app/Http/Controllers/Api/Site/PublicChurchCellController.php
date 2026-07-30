<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ChurchCell;
use Illuminate\Http\JsonResponse;

/**
 * API publique des cellules de maison CMP.
 */
final class PublicChurchCellController extends Controller
{
    /**
     * Liste les cellules actives pour la page publique.
     *
     * @return JsonResponse `{ data: Cell[] }`
     */
    public function index(): JsonResponse
    {
        $items = ChurchCell::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(static function (ChurchCell $cell): array {
                return [
                    'id' => (string) $cell->id,
                    'name' => $cell->name,
                    'commune' => $cell->commune,
                    'day' => $cell->day ?? '',
                    'time' => $cell->time ?? '',
                    'host' => $cell->host ?? '',
                    'description' => $cell->description ?? '',
                    'address' => $cell->address ?? '',
                    'lat' => $cell->lat,
                    'lng' => $cell->lng,
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }
}
