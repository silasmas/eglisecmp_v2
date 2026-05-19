<?php

declare(strict_types=1);

namespace App\Filament\Resources\DailyVerseResource\Pages;

use App\Filament\Resources\DailyVerseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyVerse extends CreateRecord
{
    protected static string $resource = DailyVerseResource::class;
}
