<?php

namespace App\Filament\Resources\RichVideoResource\Pages;

use App\Filament\Resources\RichVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichVideos extends ListRecords
{
    protected static string $resource = RichVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
