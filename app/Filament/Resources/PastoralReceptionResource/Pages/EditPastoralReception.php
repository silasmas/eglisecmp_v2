<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\PastoralReceptionResource;
use App\Models\SiteInquiry;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/** Édition du dossier pastoral (notes / conclusion). */
class EditPastoralReception extends EditRecord
{
    protected static string $resource = PastoralReceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var SiteInquiry $record */
        $record = $this->record;

        if (($data['reception_status'] ?? null) === SiteInquiry::RECEPTION_IN_PROGRESS
            && $record->received_at === null) {
            $data['received_at'] = now();
        }

        if (($data['reception_status'] ?? null) === SiteInquiry::RECEPTION_COMPLETED) {
            $data['completed_at'] = now();
        }

        if (($data['reception_status'] ?? null) === SiteInquiry::RECEPTION_AWAITING
            || ($data['reception_status'] ?? null) === null) {
            $data['reception_status'] = $data['reception_status'] ?? SiteInquiry::RECEPTION_AWAITING;
        }

        return $data;
    }
}
