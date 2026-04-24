<?php

namespace App\Filament\Resources\RichMessageResource\Pages;

use App\Filament\Resources\RichMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichMessages extends ListRecords
{
    protected static string $resource = RichMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
