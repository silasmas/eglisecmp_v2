<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchWorker;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ChurchWorkerApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;
use Throwable;

/**
 * Validation d'un ouvrier : crée le user « ouvrier » et notifie.
 */
final class ChurchWorkerApprovalService
{
    public const ROLE_NAME = 'ouvrier';

    public const ROLE_DISPLAY = 'Ouvrier';

    /**
     * Approuve le dossier, crée/lie le compte user et notifie l'ouvrier.
     *
     * L’approbation en base est toujours commitée ; un échec SMTP
     * n’annule pas la validation (l’e-mail est best-effort).
     *
     * @param  ChurchWorker  $worker  Dossier à valider
     * @param  User  $reviewer  Responsable qui valide
     * @return ChurchWorker  Dossier rafraîchi (status approved)
     */
    public function approve(ChurchWorker $worker, User $reviewer): ChurchWorker
    {
        $approved = DB::transaction(function () use ($worker, $reviewer): ChurchWorker {
            $this->ensureOuvrierRole();

            $user = $worker->user;

            if ($user === null) {
                $email = $worker->email;
                if ($email === null || $email === '') {
                    $email = 'ouvrier.'.$worker->id.'@cmp.local';
                }

                $existing = User::query()->where('email', $email)->first();
                if ($existing !== null) {
                    $user = $existing;
                } else {
                    $legacyRole = Role::query()->where('name', self::ROLE_NAME)->first();
                    $user = User::query()->create([
                        'name' => $worker->fullName(),
                        'email' => $email,
                        'password' => Hash::make(Str::password(16)),
                        'role_id' => $legacyRole?->id,
                        'role' => self::ROLE_NAME,
                        'notifiable' => true,
                        'avatar' => $worker->photo_path,
                    ]);
                }
            }

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([self::ROLE_NAME]);
            }

            $worker->update([
                'user_id' => $user->id,
                'status' => ChurchWorker::STATUS_APPROVED,
                'rejection_reason' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $worker->refresh();
        });

        $this->notifyApproved($approved);

        return $approved;
    }

    /**
     * Refuse une inscription.
     *
     * @param  ChurchWorker  $worker  Dossier à refuser
     * @param  User  $reviewer  Responsable qui refuse
     * @param  string|null  $reason  Motif optionnel
     * @return ChurchWorker  Dossier rafraîchi
     */
    public function reject(ChurchWorker $worker, User $reviewer, ?string $reason = null): ChurchWorker
    {
        $worker->update([
            'status' => ChurchWorker::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $worker->refresh();
    }

    /**
     * Marque le badge comme généré / validé.
     *
     * @param  ChurchWorker  $worker  Ouvrier approuvé
     * @return ChurchWorker  Dossier rafraîchi
     */
    public function generateBadge(ChurchWorker $worker): ChurchWorker
    {
        if ($worker->status !== ChurchWorker::STATUS_APPROVED) {
            abort(422, 'L’ouvrier doit être validé avant la génération du badge.');
        }

        $worker->update([
            'badge_generated' => true,
            'badge_generated_at' => now(),
            'badge_token' => $worker->badge_token ?: (string) Str::uuid(),
        ]);

        return $worker->refresh();
    }

    /**
     * Envoie l’e-mail de validation sans faire échouer l’approbation.
     *
     * @param  ChurchWorker  $worker  Ouvrier déjà approuvé
     */
    private function notifyApproved(ChurchWorker $worker): void
    {
        if (! filled($worker->email) || ! filter_var($worker->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $user = $worker->user;
        if ($user === null) {
            return;
        }

        try {
            $user->notify(new ChurchWorkerApprovedNotification($worker));
        } catch (Throwable $throwable) {
            Log::warning('Échec envoi e-mail validation ouvrier (approbation conservée).', [
                'worker_id' => $worker->id,
                'email' => $worker->email,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Garantit le rôle Spatie + legacy « ouvrier ».
     */
    private function ensureOuvrierRole(): void
    {
        Role::query()->firstOrCreate(
            ['name' => self::ROLE_NAME],
            [
                'display_name' => self::ROLE_DISPLAY,
                'guard_name' => 'web',
            ]
        );

        SpatieRole::findOrCreate(self::ROLE_NAME, 'web');
    }
}
