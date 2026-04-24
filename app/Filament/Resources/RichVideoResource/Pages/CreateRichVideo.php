<?php

namespace App\Filament\Resources\RichVideoResource\Pages;

use App\Actions\RichVideo\CreateRichVideoAction;
use App\DataTransferObjects\RichVideoData;
use App\Filament\Resources\RichVideoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRichVideo extends CreateRecord
{
    protected static string $resource = RichVideoResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $richVideo = DB::transaction(
            fn () => app(CreateRichVideoAction::class)->execute(
                RichVideoData::fromArray($data),
            ),
        );

        return $richVideo;
    }
}
