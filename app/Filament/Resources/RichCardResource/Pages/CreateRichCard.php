<?php

namespace App\Filament\Resources\RichCardResource\Pages;

use App\Actions\RichCard\CreateRichCardAction;
use App\DataTransferObjects\RichCardData;
use App\Filament\Resources\RichCardResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRichCard extends CreateRecord
{
    protected static string $resource = RichCardResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $richCard = DB::transaction(
            fn () => app(CreateRichCardAction::class)->execute(
                RichCardData::fromArray($data),
            ),
        );

        return $richCard;
    }
}
