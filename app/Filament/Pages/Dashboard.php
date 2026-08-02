<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Widgets\AdminRoleWelcomeWidget;
use App\Filament\Widgets\ChildPresentationStatsOverviewWidget;
use App\Filament\Widgets\ChurchWorkersOverviewWidget;
use App\Filament\Widgets\PastoralAppointmentStatsOverviewWidget;
use App\Filament\Widgets\SiteContentMixChartWidget;
use App\Filament\Widgets\SiteInquiriesTrendChartWidget;
use App\Filament\Widgets\SiteResourcesOverviewWidget;
use App\Filament\Widgets\SiteTransactionsTrendChartWidget;
use App\Filament\Widgets\WorshipAttendanceStatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Tableau de bord admin : widgets filtrés selon rôles / permissions.
 */
class Dashboard extends BaseDashboard
{
    use ProvidesAdminTourStep;

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            AdminRoleWelcomeWidget::class,
            ChurchWorkersOverviewWidget::class,
            PastoralAppointmentStatsOverviewWidget::class,
            ChildPresentationStatsOverviewWidget::class,
            WorshipAttendanceStatsOverviewWidget::class,
            SiteResourcesOverviewWidget::class,
            SiteInquiriesTrendChartWidget::class,
            SiteTransactionsTrendChartWidget::class,
            SiteContentMixChartWidget::class,
        ];
    }

    public static function getTourStepId(): ?string
    {
        return 'dashboard';
    }

    public static function getTourStepTitle(): ?string
    {
        return 'Tableau de bord';
    }

    public static function getTourStepDescription(): ?string
    {
        return 'Vue d’accueil personnalisée selon votre rôle et vos permissions.';
    }

    /**
     * @return list<string>
     */
    public static function getTourStepFeatures(): array
    {
        return [
            'Voir les indicateurs de vos modules autorisés',
            'Suivre l’activité récente (demandes, paiements, contenu)',
            'Relancer la visite guidée via l’icône 🎓',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 1;
    }
}
