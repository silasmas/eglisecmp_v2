<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Studio badges ouvriers (UI adaptée du module retraite), réservé aux sessions admin.
 */
final class WorkerBadgeStudioController extends Controller
{
    /**
     * Affiche le studio si l’utilisateur est connecté et autorisé.
     *
     * Sans session valide → redirection vers le login Filament.
     */
    public function __invoke(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return redirect()->guest(url('/admin/login'));
        }

        if (! $this->canAccessStudio($user)) {
            abort(403, 'Accès au studio badges réservé aux administrateurs / gestionnaires ouvriers.');
        }

        return response()
            ->view('worker-badge-studio', [
                'assetBase' => url('/worker-badge-studio'),
                'workersAdminUrl' => url('/admin/church-workers'),
                'departmentsAdminUrl' => url('/admin/church-departments'),
                'qrLinksAdminUrl' => \App\Filament\Pages\PublicQrLinksPage::getUrl(),
                'userName' => $user->name,
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Droits d’accès au studio (même logique que ressources Ouvriers).
     */
    private function canAccessStudio(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('ViewAny:ChurchWorker')
            || $user->can('ViewAny:ChurchDepartment')
            || $user->can('Update:ChurchWorker');
    }
}
