<?php

namespace App\Filament\Resources\TalkResource\Pages;

use App\Actions\LineMessage\PushMessageAction;
use App\Filament\Resources\TalkResource;
use App\Models\Friend;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTalk extends CreateRecord
{
    protected static string $resource = TalkResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $friend = Friend::whereId($data['receiver'])->first();

        return DB::transaction(
            fn () => app(PushMessageAction::class)
                ->execute($data['message']['text'], $friend)
        );
    }
}
