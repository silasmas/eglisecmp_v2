<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SiteInquiry;
use App\Models\User;
use App\Notifications\SitePrayerRequestSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * Envoie les notifications d’intercession pour une requête de prière enregistrée.
 */
class PrayerRequestNotificationService
{
    /**
     * Notifie les utilisateurs ayant un rôle intercession configuré et existant en base.
     *
     * @param  SiteInquiry  $inquiry  Requête de prière persistée.
     */
    public function notify(SiteInquiry $inquiry): void
    {
        $recipients = $this->resolveRecipients();

        if ($recipients->isEmpty()) {
            Log::warning('Requête de prière enregistrée sans destinataire intercession.', [
                'inquiry_id' => $inquiry->id,
                'roles_config' => config('site_public.prayer_notification_roles', []),
            ]);

            return;
        }

        foreach ($recipients as $user) {
            $user->notify(new SitePrayerRequestSubmittedNotification($inquiry));
        }
    }

    /**
     * Résout les destinataires sans lever d’exception si un rôle configuré est absent.
     *
     * @return Collection<int, User>
     */
    private function resolveRecipients(): Collection
    {
        $configuredRoles = config('site_public.prayer_notification_roles', ['intercession']);
        $guard = (string) config('auth.defaults.guard', 'web');

        $existingRoleNames = Role::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $configuredRoles)
            ->pluck('name')
            ->all();

        if ($existingRoleNames === []) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', function ($query) use ($existingRoleNames): void {
                $query->whereIn('name', $existingRoleNames);
            })
            ->get();
    }
}
