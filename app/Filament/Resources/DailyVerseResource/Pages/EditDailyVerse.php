<?php

declare(strict_types=1);

namespace App\Filament\Resources\DailyVerseResource\Pages;

use App\Filament\Resources\DailyVerseResource;
use Filament\Resources\Pages\EditRecord;

class EditDailyVerse extends EditRecord
{
    protected static string $resource = DailyVerseResource::class;
}
