<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Enregistre chaque tentative / connexion réussie au dashboard.
 */
final class RecordUserLogin
{
    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * Connexion réussie.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        LoginHistory::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'ip_address' => $this->request->ip(),
            'user_agent' => (string) $this->request->userAgent(),
            'guard' => $event->guard,
            'status' => LoginHistory::STATUS_SUCCESS,
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Tentative échouée.
     */
    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $email = is_string($credentials['email'] ?? null) ? $credentials['email'] : null;
        $user = $event->user instanceof User ? $event->user : null;

        LoginHistory::query()->create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'name' => $user?->name,
            'ip_address' => $this->request->ip(),
            'user_agent' => (string) $this->request->userAgent(),
            'guard' => $event->guard,
            'status' => LoginHistory::STATUS_FAILED,
            'logged_in_at' => now(),
        ]);
    }
}
