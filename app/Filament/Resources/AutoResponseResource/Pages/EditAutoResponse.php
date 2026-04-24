<?php

namespace App\Filament\Resources\AutoResponseResource\Pages;

use App\Actions\AutoResponse\DeleteAutoResponseAction;
use App\Actions\AutoResponse\EditAutoResponseAction;
use App\DataTransferObjects\AutoResponseData;
use App\Enums\MessageTypeEnum;
use App\Filament\Resources\AutoResponseResource;
use App\Models\AutoResponse;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class EditAutoResponse extends EditRecord
{
    protected static string $resource = AutoResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->using(
                function (AutoResponse $record) {
                    try {
                        return app(DeleteAutoResponseAction::class)->execute($record);
                    } catch (LogicException) {
                        return false;
                    }
                }
            ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $autoResponse = DB::transaction(
            fn () => app(EditAutoResponseAction::class)->execute(AutoResponseData::fromArray($data), $record));

        $record->messageDelivery->message_id = $data['message_id'];

        $messageType = null;
        switch ($data['message_type']) {
            case MessageTypeEnum::MESSAGE->value:
                $messageType = Message::class;
                break;
            case MessageTypeEnum::RICH_MESSAGE->value:
                $messageType = RichMessage::class;
                break;
            case MessageTypeEnum::RICH_VIDEO->value:
                $messageType = RichVideo::class;
                break;
            case MessageTypeEnum::RICH_CARD->value:
                $messageType = RichCard::class;
                break;
        }

        $record->messageDelivery->message_id = $data['message_id'];

        $record->messageDelivery->message_type = $messageType;

        $record->messageDelivery->save();

        return $autoResponse;
    }
}
