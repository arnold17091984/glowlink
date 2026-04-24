<?php

namespace App\Filament\Resources\RichMenuSetResource\Pages;

use App\Filament\Resources\RichMenuSetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichMenuSets extends ListRecords
{
    protected static string $resource = RichMenuSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
