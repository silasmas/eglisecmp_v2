<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force la locale française pour les réponses JSON de l’API site public.
 */
class SetSiteApiLocale
{
    /**
     * @param  Request  $request  Requête entrante.
     * @param  Closure(Request): Response  $next  Suite du pipeline HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale((string) config('app.locale', 'fr'));

        return $next($request);
    }
}
