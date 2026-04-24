<?php

namespace App\Filament\Resources\RichCardResource\Pages;

use App\Filament\Resources\RichCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichCards extends ListRecords
{
    protected static string $resource = RichCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
