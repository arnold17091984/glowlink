<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListMessageActivities extends ListActivities
{
    protected static string $resource = MessageResource::class;
}
