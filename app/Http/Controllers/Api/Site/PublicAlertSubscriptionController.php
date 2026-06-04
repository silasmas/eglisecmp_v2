<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Services\Alert\AlertSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API publique : abonnement et désabonnement aux alertes live / événements.
 */
class PublicAlertSubscriptionController extends Controller
{
    /**
     * Crée ou met à jour un abonnement opt-in.
     */
    public function store(Request $request, AlertSubscriptionService $service): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:190|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'name' => 'nullable|string|max:120',
            'notify_live' => 'required|boolean',
            'notify_events' => 'required|boolean',
            'source' => 'nullable|string|max:40|in:testimony,events,live,footer',
        ]);

        $notifyLive = (bool) $validated['notify_live'];
        $notifyEvents = (bool) $validated['notify_events'];

        if (! $notifyLive && ! $notifyEvents) {
            return response()->json([
                'message' => 'Cochez au moins une option : live ou événements.',
            ], 422);
        }

        try {
            $result = $service->subscribe(
                $validated['email'] ?? null,
                $validated['phone'] ?? null,
                $notifyLive,
                $notifyEvents,
                (string) ($validated['source'] ?? 'footer'),
                $validated['name'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'ok' => true,
                'created' => $result['created'],
                'message' => 'Merci ! Vous recevrez nos alertes selon vos choix.',
            ],
        ], $result['created'] ? 201 : 200);
    }

    /**
     * Désabonne toutes les alertes via le jeton reçu par e-mail.
     */
    public function unsubscribe(string $token, AlertSubscriptionService $service): JsonResponse
    {
        $ok = $service->unsubscribeByToken($token);

        if (! $ok) {
            return response()->json([
                'data' => ['ok' => false, 'message' => 'Lien de désabonnement invalide ou déjà utilisé.'],
            ], 404);
        }

        return response()->json([
            'data' => [
                'ok' => true,
                'message' => 'Vous ne recevrez plus d’alertes live ni d’événements de notre part.',
            ],
        ]);
    }
}
