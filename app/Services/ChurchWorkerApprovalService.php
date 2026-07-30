<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchWorker;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ChurchWorkerApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Validation d'un ouvrier : crée le user « ouvrier » et notifie.
 */
final class ChurchWorkerApprovalService
{
    public const ROLE_NAME = 'ouvrier';

    public const ROLE_DISPLAY = 'Ouvrier';

    /**
     * Approuve le dossier, crée/lie le compte user et notifie l'ouvrier.
     */
    public function approve(ChurchWorker $worker, User $reviewer): ChurchWorker
    {
        return DB::transaction(function () use ($worker, $reviewer): ChurchWorker {
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

            $worker->refresh();

            if (filled($worker->email) && filter_var($worker->email, FILTER_VALIDATE_EMAIL)) {
                $user->notify(new ChurchWorkerApprovedNotification($worker));
            }

            return $worker;
        });
    }

    /**
     * Refuse une inscription.
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
