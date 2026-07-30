<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChildPresentationResource\Pages;

use App\Filament\Resources\ChildPresentationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

/**
 * Consultation d'une demande de présentation d'enfant.
 */
class ViewChildPresentation extends ViewRecord
{
    protected static string $resource = ChildPresentationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChildPresentationResource::makeConfirmAction(),
            ChildPresentationResource::makeDeclineAction(),
            Action::make('back')
                ->label('Retour')
                ->url(ChildPresentationResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
