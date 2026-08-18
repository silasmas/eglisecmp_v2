<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoFormResource\Pages;

use App\Filament\Resources\GuestInfoFormResource;
use App\Models\GuestInfoForm;
use App\Services\GuestFormSubmissionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/** Création d’un formulaire d’accueil. */
class CreateGuestInfoForm extends CreateRecord
{
    protected static string $resource = GuestInfoFormResource::class;

    private string $plainPasswordToRemember = '';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? 'form')).'-'.Str::lower(Str::random(4));
        }

        $plain = (string) ($data['plain_password'] ?? '');
        unset($data['plain_password']);

        if ($plain === '') {
            $plain = Str::password(10, symbols: false);
        }

        $data['access_password'] = bcrypt($plain);
        $this->plainPasswordToRemember = $plain;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var GuestInfoForm $record */
        $record = $this->record;
        if ($this->plainPasswordToRemember !== '') {
            app(GuestFormSubmissionService::class)->rememberPlainPassword($record, $this->plainPasswordToRemember);
            \Filament\Notifications\Notification::make()
                ->title('Mot de passe départements')
                ->body($this->plainPasswordToRemember)
                ->success()
                ->persistent()
                ->send();
        }
    }
}
