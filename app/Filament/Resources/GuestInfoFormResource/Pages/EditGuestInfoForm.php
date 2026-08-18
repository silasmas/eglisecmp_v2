<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoFormResource\Pages;

use App\Filament\Resources\GuestInfoFormResource;
use App\Models\GuestInfoForm;
use App\Services\GuestFormSubmissionService;
use App\Services\GuestInfoFormPdfTemplateService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

/** Édition d’un formulaire d’accueil. */
class EditGuestInfoForm extends EditRecord
{
    protected static string $resource = GuestInfoFormResource::class;

    private ?string $plainPasswordToRemember = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seedPdf')
                ->label('Charger template PDF')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var GuestInfoForm $record */
                    $record = $this->getRecord();
                    $deptIds = $record->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
                    app(GuestInfoFormPdfTemplateService::class)->applyToForm($record, $deptIds);
                    Notification::make()->title('Template PDF chargé')->success()->send();
                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? 'form')).'-'.Str::lower(Str::random(4));
        }

        $plain = (string) ($data['plain_password'] ?? '');
        unset($data['plain_password']);

        if ($plain !== '') {
            $data['access_password'] = bcrypt($plain);
            $this->plainPasswordToRemember = $plain;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->plainPasswordToRemember !== null) {
            /** @var GuestInfoForm $record */
            $record = $this->getRecord();
            app(GuestFormSubmissionService::class)->rememberPlainPassword($record, $this->plainPasswordToRemember);
            Notification::make()
                ->title('Mot de passe mis à jour')
                ->body($this->plainPasswordToRemember)
                ->success()
                ->persistent()
                ->send();
        }
    }
}
