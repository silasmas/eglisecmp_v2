<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Http\JsonResponse;

/**
 * Statut du live YouTube de la chaîne de l’église (lecture seule).
 */
final class PublicYoutubeLiveController extends Controller
{
    /**
     * @return JsonResponse `{ data: YoutubeLivePayload | null }`
     */
    public function show(YoutubeLiveStatusService $youtubeLive): JsonResponse
    {
        return response()->json([
            'data' => $youtubeLive->current(),
        ]);
    }
}
