<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ChurchDirectoryImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Page admin : modèles Excel + import (départements, cellules, extensions).
 */
class ChurchDirectoryImportPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Import Excel';

    protected static ?string $title = 'Import Excel — Départements, cellules, extensions';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 29;

    protected string $view = 'filament.pages.church-directory-import';

    public string $lastMessage = '';

    /**
     * Accès : super_admin ou droit de création sur au moins une ressource concernée.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->hasRole('super_admin')
            || $user->can('Create:ChurchDepartment')
            || $user->can('Create:ChurchCell')
            || $user->can('Create:ChurchExtension')
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'types' => ChurchDirectoryImportService::types(),
            'lastMessage' => $this->lastMessage,
            'templates' => [
                ChurchDirectoryImportService::TYPE_DEPARTMENTS => [
                    'label' => 'Départements',
                    'columns' => implode(' | ', ChurchDirectoryImportService::headersFor(ChurchDirectoryImportService::TYPE_DEPARTMENTS)),
                ],
                ChurchDirectoryImportService::TYPE_CELLS => [
                    'label' => 'Cellules',
                    'columns' => implode(' | ', ChurchDirectoryImportService::headersFor(ChurchDirectoryImportService::TYPE_CELLS)),
                ],
                ChurchDirectoryImportService::TYPE_EXTENSIONS => [
                    'label' => 'Extensions',
                    'columns' => implode(' | ', ChurchDirectoryImportService::headersFor(ChurchDirectoryImportService::TYPE_EXTENSIONS)),
                ],
            ],
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDepartments')
                ->label('Modèle départements')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => ChurchDirectoryImportService::downloadTemplate(ChurchDirectoryImportService::TYPE_DEPARTMENTS)),
            Action::make('downloadCells')
                ->label('Modèle cellules')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => ChurchDirectoryImportService::downloadTemplate(ChurchDirectoryImportService::TYPE_CELLS)),
            Action::make('downloadExtensions')
                ->label('Modèle extensions')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => ChurchDirectoryImportService::downloadTemplate(ChurchDirectoryImportService::TYPE_EXTENSIONS)),
            Action::make('importFile')
                ->label('Importer Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Select::make('type')
                        ->label('Type')
                        ->options(ChurchDirectoryImportService::types())
                        ->required()
                        ->native(false),
                    FileUpload::make('file')
                        ->label('Fichier Excel (.xlsx / .xls / .csv)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/octet-stream',
                        ])
                        ->disk('local')
                        ->directory('imports/excel')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = is_array($data['file'] ?? null)
                        ? (string) ($data['file'][0] ?? '')
                        : (string) ($data['file'] ?? '');

                    if ($path === '' || ! Storage::disk('local')->exists($path)) {
                        Notification::make()->title('Fichier introuvable')->danger()->send();

                        return;
                    }

                    $absolute = Storage::disk('local')->path($path);
                    $result = app(ChurchDirectoryImportService::class)->importFromPath(
                        (string) ($data['type'] ?? ''),
                        $absolute,
                    );
                    Storage::disk('local')->delete($path);

                    $this->lastMessage = $result['message'];
                    if ($result['errors'] !== []) {
                        $this->lastMessage .= "\n".implode("\n", $result['errors']);
                    }

                    $notification = Notification::make()
                        ->title($result['success'] ? 'Import terminé' : 'Import partiel')
                        ->body($result['message']);

                    if ($result['success']) {
                        $notification->success()->send();
                    } else {
                        $notification->warning()->send();
                    }
                }),
        ];
    }

    /**
     * Télécharge un modèle Excel depuis la vue.
     *
     * @param  string  $type  Type d’import
     */
    public function downloadTemplate(string $type): StreamedResponse
    {
        return ChurchDirectoryImportService::downloadTemplate($type);
    }
}
