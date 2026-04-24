<?php

namespace App\Filament\Resources\RichCardResource\Pages;

use App\Filament\Resources\RichCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRichCard extends EditRecord
{
    protected static string $resource = RichCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
