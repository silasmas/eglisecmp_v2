<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Affiche la page publique du badge ouvrier (moule retraite, hors SPA site).
 */
final class WorkerBadgePublicController extends Controller
{
    /**
     * Rend la vue module badge pour un token public.
     *
     * @param  Request  $request  Requête HTTP courante.
     * @param  string  $token  Jeton public du badge ouvrier.
     * @return View
     */
    public function __invoke(Request $request, string $token): View
    {
        return view('worker-badge-public', [
            'token' => $token,
            'badgePublicUrl' => $request->url(),
        ]);
    }
}
