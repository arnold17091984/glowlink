<?php

namespace App\Filament\Resources\RichVideoResource\Pages;

use App\Filament\Resources\RichVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRichVideo extends EditRecord
{
    protected static string $resource = RichVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
