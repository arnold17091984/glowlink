<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Actions\Broadcast\DeleteBroadcastAction;
use App\Filament\Resources\BroadcastResource;
use App\Models\Broadcast;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use LogicException;

class ViewBroadcast extends ViewRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()->using(
                function (Broadcast $record) {
                    try {
                        return app(DeleteBroadcastAction::class)->execute($record);
                    } catch (LogicException) {
                        return false;
                    }
                }
            ),
        ];
    }
}
