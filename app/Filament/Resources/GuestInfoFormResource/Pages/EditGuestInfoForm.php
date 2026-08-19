<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoFormResource\Pages;

use App\Filament\Resources\GuestInfoFormResource;
use App\Models\GuestInfoForm;
use App\Models\GuestPastor;
use App\Services\GuestFormSubmissionService;
use App\Services\GuestInfoFormPdfTemplateService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Édition d’un formulaire d’accueil. */
class EditGuestInfoForm extends EditRecord
{
    protected static string $resource = GuestInfoFormResource::class;

    private ?string $plainPasswordToRemember = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Aperçu PC / Mobile')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->modalHeading('Aperçu du formulaire')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer')
                ->modalWidth('7xl')
                ->modalContent(function () {
                    /** @var GuestInfoForm $record */
                    $record = $this->getRecord()->load(['sections.fields', 'project.guestPastors']);
                    $pastor = $record->project?->guestPastors?->first();

                    return view('filament.guest-forms.preview', [
                        'form' => $record,
                        'pastor' => $pastor,
                        'headline' => 'Formulaire de préparation pour mieux s’occuper du pasteur '.($pastor?->full_name ?? '…'),
                        'pastorPhotoUrl' => $this->resolvePastorPhotoUrl($pastor),
                    ]);
                }),
            Action::make('seedPdf')
                ->label('Charger template PDF')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Remplacer par le template PDF ?')
                ->modalDescription('Les rubriques actuelles seront effacées. Le mode Assistant (étapes) sera activé.')
                ->action(function () {
                    /** @var GuestInfoForm $record */
                    $record = $this->getRecord();
                    $deptIds = $record->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
                    app(GuestInfoFormPdfTemplateService::class)->applyToForm($record, $deptIds);
                    $record->update(['layout_mode' => GuestInfoForm::LAYOUT_WIZARD]);

                    Notification::make()
                        ->title('Template PDF chargé')
                        ->body('Mode Assistant activé. La page se recharge…')
                        ->success()
                        ->send();

                    return redirect(GuestInfoFormResource::getUrl('edit', ['record' => $record]));
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * URL publique de la photo du pasteur invité.
     */
    private function resolvePastorPhotoUrl(?GuestPastor $pastor): ?string
    {
        if ($pastor === null || blank($pastor->photo_path)) {
            return null;
        }

        $path = (string) $pastor->photo_path;

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
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

        if (blank($data['layout_mode'] ?? null)) {
            $data['layout_mode'] = GuestInfoForm::LAYOUT_WIZARD;
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
