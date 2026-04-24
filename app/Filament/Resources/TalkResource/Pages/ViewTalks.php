<?php

namespace App\Filament\Resources\TalkResource\Pages;

use App\Filament\Resources\TalkResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTalks extends ViewRecord
{
    protected static string $resource = TalkResource::class;

    protected static ?string $recordTitleAttribute = 'receiver.name';
}
