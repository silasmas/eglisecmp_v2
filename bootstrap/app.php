<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $throwable, Request $request) {
            if (! $request->is('api/site/*')) {
                return null;
            }

            app()->setLocale((string) config('app.locale', 'fr'));

            if ($throwable instanceof ValidationException) {
                $firstMessage = collect($throwable->errors())->flatten()->first();

                return response()->json([
                    'message' => is_string($firstMessage) && $firstMessage !== ''
                        ? $firstMessage
                        : 'Les informations envoyées ne sont pas valides.',
                    'errors' => $throwable->errors(),
                ], 422);
            }

            $status = $throwable instanceof HttpExceptionInterface
                ? $throwable->getStatusCode()
                : 500;

            if ($status >= 500 && ! config('app.debug')) {
                return response()->json([
                    'message' => 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
                ], $status);
            }

            $message = trim($throwable->getMessage());

            if ($message === '' || str_contains($message, 'role named')) {
                return response()->json([
                    'message' => 'Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.',
                ], $status >= 400 ? $status : 500);
            }

            return response()->json([
                'message' => $message,
            ], $status >= 400 ? $status : 500);
        });
    })->create();
