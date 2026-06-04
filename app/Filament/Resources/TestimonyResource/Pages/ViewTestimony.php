<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonyResource\Pages;

use App\Filament\Resources\TestimonyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail d’un témoignage pour modération et consultation des coordonnées.
 */
class ViewTestimony extends ViewRecord
{
    protected static string $resource = TestimonyResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            TestimonyResource::makeApproveAction(),
            TestimonyResource::makeRejectAction(),
            DeleteAction::make(),
        ];
    }
}
