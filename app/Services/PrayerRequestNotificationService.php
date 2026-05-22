<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role as LegacyRole;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Notifications\SitePrayerRequestSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * Résout les destinataires via `role_id` (Filament) et/ou Spatie Permission.
     *
     * @return Collection<int, User>
     */
    private function resolveRecipients(): Collection
    {
        $configuredRoles = config('site_public.prayer_notification_roles', ['intercession']);

        $legacyRoleIds = LegacyRole::query()
            ->whereIn('name', $configuredRoles)
            ->pluck('id')
            ->all();

        $spatieRoleNames = LegacyRole::query()
            ->whereIn('name', $configuredRoles)
            ->whereNotNull('name')
            ->pluck('name')
            ->all();

        if ($legacyRoleIds === [] && $spatieRoleNames === []) {
            return collect();
        }

        return User::query()
            ->where(function ($query) use ($legacyRoleIds, $spatieRoleNames): void {
                if ($legacyRoleIds !== []) {
                    $query->whereIn('role_id', $legacyRoleIds);
                }
                if ($spatieRoleNames !== []) {
                    $query->orWhereHas('roles', function ($roleQuery) use ($spatieRoleNames): void {
                        $roleQuery->whereIn('name', $spatieRoleNames);
                    });
                }
            })
            ->get()
            ->unique('id')
            ->values();
    }
}
