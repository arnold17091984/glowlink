<?php

namespace App\Filament\Resources\ScenarioDeliveryResource\Pages;

use App\Filament\Resources\ScenarioDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScenarioDeliveries extends ListRecords
{
    protected static string $resource = ScenarioDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
