<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Bureau;
use App\Models\DailyVerse;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Minister;
use App\Models\MinisterReceptionSchedule;
use App\Models\Offrande;
use App\Models\Post;
use App\Models\ScheduleProgram;
use App\Models\SiteInquiry;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Vue d’ensemble des volumes enregistrés dans l’administration Filament.
 */
class SiteResourcesOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Ressources du site';

    protected ?string $description = 'Volumes actuels par module de l’administration.';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $prayerCount = SiteInquiry::query()
            ->where('kind', SiteInquiry::KIND_PRAYER)
            ->count();

        $pendingAppointments = SiteInquiry::query()
            ->where('kind', SiteInquiry::KIND_APPOINTMENT)
            ->where('appointment_status', SiteInquiry::STATUS_PENDING)
            ->count();

        $successfulTransactions = Transaction::query()
            ->where('etat', 'paid')
            ->count();

        return [
            Stat::make('Publications', (string) Post::query()->count())
                ->description('Enseignements et messages')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),
            Stat::make('Événements', (string) Event::query()->count())
                ->description('Agenda public')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Pasteurs', (string) Minister::query()->count())
                ->description('Fiches leadership')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('Galeries', (string) Gallery::query()->count())
                ->description('Albums médias')
                ->descriptionIcon('heroicon-m-photo')
                ->color('warning'),
            Stat::make('Programmes', (string) ScheduleProgram::query()->count())
                ->description('Grille hebdomadaire')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
            Stat::make('Requêtes de prière', (string) $prayerCount)
                ->description('Formulaire public')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),
            Stat::make('RDV en attente', (string) $pendingAppointments)
                ->description('À confirmer')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color('warning'),
            Stat::make('Offrandes', (string) Offrande::query()->count())
                ->description('Projets de collecte')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Transactions OK', (string) $successfulTransactions)
                ->description('Paiements réussis')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
            Stat::make('Bureaux', (string) Bureau::query()->count())
                ->description('Lieux de réception')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
            Stat::make('Horaires pasteurs', (string) MinisterReceptionSchedule::query()->count())
                ->description('Créneaux RDV')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            Stat::make('Versets du jour', (string) DailyVerse::query()->count())
                ->description('Parole quotidienne')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
            Stat::make('Utilisateurs', (string) User::query()->count())
                ->description('Comptes admin')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
