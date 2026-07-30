<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\DatabaseSyncRunner;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Page admin Système : exécution des migrations / seeders et sync de la base.
 */
class DatabaseSyncPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Sync base de données';

    protected static ?string $title = 'Synchronisation de la base de données';

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?int $navigationSort = 85;

    protected string $view = 'filament.pages.database-sync';

    public string $lastOutput = '';

    /**
     * Réservé aux super_admin (opérations sensibles).
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $token = (string) config('app.deploy_token');
        $migrateUrl = $token !== ''
            ? url('/deploy/migrate/'.$token)
            : null;
        $seedUrl = $token !== ''
            ? url('/deploy/seed/'.$token)
            : null;

        return [
            'status' => DatabaseSyncRunner::status(),
            'lastOutput' => $this->lastOutput,
            'migrateHttpUrl' => $migrateUrl,
            'seedHttpUrl' => $seedUrl,
            'safeSeeders' => DatabaseSyncRunner::safeSeederLabels(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('runMigrate')
                ->label('Exécuter les migrations')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Lancer php artisan migrate --force')
                ->modalDescription('Applique les migrations une par une. En cas d’erreur (table déjà présente, migration legacy, etc.), elle est ignorée et la sync continue.')
                ->action(function (): void {
                    $result = DatabaseSyncRunner::migrate('filament');
                    $this->lastOutput = $result['output'];

                    if ($result['success']) {
                        Notification::make()
                            ->title('Migrations exécutées')
                            ->body('La base a été synchronisée avec les fichiers de migration.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Échec des migrations')
                        ->body($result['error'] ?? $result['output'])
                        ->danger()
                        ->send();
                }),
            Action::make('runSeed')
                ->label('Exécuter les seeders')
                ->icon('heroicon-o-circle-stack')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Lancer les seeders de référence')
                ->modalDescription(
                    'Exécute : '.implode(', ', DatabaseSyncRunner::safeSeederLabels()).'. '
                    .'Idempotent (updateOrCreate / skip si déjà rempli). Pas de factories ni import SQL.'
                )
                ->action(function (): void {
                    $result = DatabaseSyncRunner::seed('filament');
                    $this->lastOutput = $result['output'];

                    if ($result['success']) {
                        Notification::make()
                            ->title('Seeders exécutés')
                            ->body('Départements, extensions et stats de référence synchronisés.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Seeders partiels')
                        ->body($result['error'] ?? $result['output'])
                        ->warning()
                        ->send();
                }),
            Action::make('refreshStatus')
                ->label('Vérifier le statut')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    $result = DatabaseSyncRunner::migrateStatus('filament');
                    $this->lastOutput = $result['output'];

                    Notification::make()
                        ->title('Statut des migrations')
                        ->body(DatabaseSyncRunner::status()['pending_count'].' migration(s) en attente.')
                        ->success()
                        ->send();
                }),
            Action::make('syncShield')
                ->label('Sync permissions Shield')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Régénérer les permissions Filament Shield')
                ->modalDescription('Utile après l’ajout de nouveaux modules (resources / pages). Peut prendre quelques secondes.')
                ->action(function (): void {
                    $result = DatabaseSyncRunner::syncShield('filament');
                    $this->lastOutput = $result['output'];

                    if ($result['success']) {
                        Notification::make()
                            ->title('Permissions synchronisées')
                            ->body('Shield a régénéré les permissions du panel admin.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Échec Shield')
                        ->body($result['error'] ?? $result['output'])
                        ->danger()
                        ->send();
                }),
        ];
    }

    /**
     * Relance uniquement les migrations (bouton dans la vue).
     */
    public function runMigrations(): void
    {
        $result = DatabaseSyncRunner::migrate('filament');
        $this->lastOutput = $result['output'];

        if ($result['success']) {
            Notification::make()
                ->title('Migrations exécutées')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Échec des migrations')
            ->body($result['error'] ?? $result['output'])
            ->danger()
            ->send();
    }

    /**
     * Relance les seeders sûrs (bouton dans la vue).
     */
    public function runSeeders(): void
    {
        $result = DatabaseSyncRunner::seed('filament');
        $this->lastOutput = $result['output'];

        if ($result['success']) {
            Notification::make()
                ->title('Seeders exécutés')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Seeders partiels')
            ->body($result['error'] ?? $result['output'])
            ->warning()
            ->send();
    }
}
