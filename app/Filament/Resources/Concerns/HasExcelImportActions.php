<?php

declare(strict_types=1);

namespace App\Filament\Resources\Concerns;

use App\Services\ChurchDirectoryImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Actions Filament communes : télécharger le modèle Excel + importer un fichier.
 */
trait HasExcelImportActions
{
    /**
     * Actions d’en-tête pour un type d’import donné.
     *
     * @param  string  $type  departements|cellules|extensions
     * @return list<Action>
     */
    protected function excelImportHeaderActions(string $type): array
    {
        return [
            Action::make('downloadExcelTemplate')
                ->label('Modèle Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => ChurchDirectoryImportService::downloadTemplate($type)),
            Action::make('importExcelFile')
                ->label('Importer Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Select::make('type')
                        ->label('Type')
                        ->options(ChurchDirectoryImportService::types())
                        ->default($type)
                        ->required(),
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
                        ->required()
                        ->helperText('Téléchargez le modèle, complétez-le dans Excel, puis réimportez le fichier ici.'),
                ])
                ->action(function (array $data) use ($type): void {
                    $path = is_array($data['file'] ?? null)
                        ? (string) ($data['file'][0] ?? '')
                        : (string) ($data['file'] ?? '');

                    if ($path === '' || ! Storage::disk('local')->exists($path)) {
                        Notification::make()
                            ->title('Fichier introuvable')
                            ->danger()
                            ->send();

                        return;
                    }

                    $absolute = Storage::disk('local')->path($path);
                    $result = app(ChurchDirectoryImportService::class)->importFromPath(
                        (string) ($data['type'] ?? $type),
                        $absolute,
                    );

                    Storage::disk('local')->delete($path);

                    $notification = Notification::make()
                        ->title($result['success'] ? 'Import terminé' : 'Import partiel')
                        ->body($result['message'].(
                            $result['errors'] !== []
                                ? "\n".implode("\n", array_slice($result['errors'], 0, 5))
                                : ''
                        ));

                    if ($result['success']) {
                        $notification->success()->send();
                    } else {
                        $notification->warning()->send();
                    }
                }),
        ];
    }
}
