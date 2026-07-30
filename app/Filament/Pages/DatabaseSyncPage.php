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
 * Page admin Système : exécution des migrations et synchronisation de la base.
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
        return [
            'status' => DatabaseSyncRunner::status(),
            'lastOutput' => $this->lastOutput,
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
                ->modalDescription('Applique les migrations en attente (ajouts / modifications de tables). Action irréversible sur la structure.')
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
}
