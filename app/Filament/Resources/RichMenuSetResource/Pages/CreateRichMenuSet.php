<?php

namespace App\Filament\Resources\RichMenuSetResource\Pages;

use App\Filament\Resources\RichMenuSetResource;
use App\Models\RichMenuSet;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRichMenuSet extends CreateRecord
{
    protected static string $resource = RichMenuSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        do {
            $reference = strtolower(strtoupper(Str::random(5)));
        } while (RichMenuSet::whereReference($reference)->exists());

        $data['reference'] = $reference;

        return $data;
    }
}
