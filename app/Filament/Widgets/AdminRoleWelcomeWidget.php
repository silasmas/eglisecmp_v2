<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Accueil dashboard : rappelle le rôle et les accès principaux de l’utilisateur.
 */
class AdminRoleWelcomeWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-role-welcome';

    /**
     * Toujours visible pour un admin authentifié.
     */
    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    /**
     * Données affichées dans la vue Blade.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [
                'name' => 'Invité',
                'roles' => [],
                'highlights' => [],
            ];
        }

        $roles = $user->getRoleNames()->values()->all();

        return [
            'name' => $user->name,
            'roles' => $roles,
            'highlights' => $this->buildHighlights($user),
        ];
    }

    /**
     * Points d’accès principaux selon les permissions.
     *
     * @return list<string>
     */
    private function buildHighlights(User $user): array
    {
        $items = [];

        if ($user->hasRole('super_admin')) {
            $items[] = 'Accès complet à l’administration CMP';
        }

        if ($user->can('ViewAny:ChurchWorker') || \App\Filament\Resources\ChurchWorkerResource::canAccess()) {
            $items[] = 'Gestion / validation des ouvriers';
        }

        if ($user->can('ViewAny:SiteInquiry') || \App\Filament\Resources\PastoralReceptionResource::canAccess()) {
            $items[] = 'Demandes de prière et rendez-vous';
        }

        if ($user->can('ViewAny:Post') || $user->can('ViewAny:Event')) {
            $items[] = 'Contenu public (publications, événements)';
        }

        if ($user->can('ViewAny:Offrande') || $user->can('ViewAny:Transaction')) {
            $items[] = 'Offrandes et paiements';
        }

        if ($user->can('ViewAny:Testimony')) {
            $items[] = 'Mur de témoignages';
        }

        if ($user->can('ViewAny:ChildPresentation') || $user->can('ViewAny:PresentedChild')) {
            $items[] = 'Présentation des enfants';
        }

        if ($user->can('ViewAny:WorshipServiceReport')) {
            $items[] = 'Statistiques de cultes';
        }

        if ($items === []) {
            $items[] = 'Consultez le menu latéral pour vos modules autorisés';
        }

        return array_values(array_unique($items));
    }
}
