<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ChurchExtension;
use Illuminate\Http\JsonResponse;

/**
 * API publique des extensions CMP.
 */
final class PublicChurchExtensionController extends Controller
{
    /**
     * Liste les extensions actives pour la carte mondiale.
     *
     * @return JsonResponse `{ data: Extension[] }`
     */
    public function index(): JsonResponse
    {
        $items = ChurchExtension::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(static function (ChurchExtension $extension): array {
                return [
                    'id' => (string) $extension->id,
                    'name' => $extension->name,
                    'city' => $extension->city,
                    'country' => $extension->country,
                    'address' => $extension->address ?? '',
                    'description' => $extension->description ?? '',
                    'lat' => (float) $extension->lat,
                    'lng' => (float) $extension->lng,
                    'leaderName' => $extension->leader_name ?? '',
                    'leaderPhotoUrl' => $extension->leaderPhotoUrl() ?? '',
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }
}
