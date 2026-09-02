<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchDepartmentResource\Pages;

use App\Filament\Resources\ChurchDepartmentResource;
use App\Filament\Resources\Concerns\HasExcelImportActions;
use App\Filament\Resources\Concerns\HasWorkerStudioActions;
use App\Services\ChurchDepartmentManagersImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

/** Liste des départements. */
class ListChurchDepartments extends ListRecords
{
    use HasExcelImportActions;
    use HasWorkerStudioActions;

    protected static string $resource = ChurchDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workerStudioHeaderActions(),
            ...$this->excelImportHeaderActions('departements'),
            Action::make('importManagersExcel')
                ->label('Importer responsables')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->modalHeading('Importer départements & responsables')
                ->modalDescription('Accepte le fichier « CREA DATABASE RESPO CMP » (Département / Responsables / Numéros / Email). Crée aussi un dossier ouvrier (en attente) pour chaque responsable.')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier Excel (.xlsx / .xls)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'application/octet-stream',
                        ])
                        ->disk('local')
                        ->directory('imports/excel')
                        ->required(),
                    Toggle::make('replace_managers')
                        ->label('Remplacer les responsables existants')
                        ->default(true)
                        ->helperText('Si activé, les responsables actuels de chaque département importé sont effacés puis recréés. Les dossiers ouvriers déjà créés sont conservés / mis à jour.'),
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
                    $result = app(ChurchDepartmentManagersImportService::class)->importFromPath(
                        $absolute,
                        (bool) ($data['replace_managers'] ?? true),
                    );

                    Storage::disk('local')->delete($path);

                    $body = $result['message'];
                    if ($result['errors'] !== []) {
                        $body .= "\n".implode("\n", array_slice($result['errors'], 0, 5));
                    }

                    $notification = Notification::make()
                        ->title($result['success'] ? 'Import responsables terminé' : 'Import partiel / en échec')
                        ->body($body)
                        ->persistent();

                    if ($result['success']) {
                        $notification->success()->send();
                    } else {
                        $notification->warning()->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
