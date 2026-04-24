<?php

namespace App\Filament\Resources\AutoResponseResource\Pages;

use App\Actions\AutoResponse\CreateAutoResponseAction;
use App\Actions\MessageDelivery\CreateMessageDeliveryAction;
use App\DataTransferObjects\AutoResponseData;
use App\DataTransferObjects\MessageDeliveryData;
use App\Enums\MessageTypeEnum;
use App\Filament\Resources\AutoResponseResource;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAutoResponse extends CreateRecord
{
    protected static string $resource = AutoResponseResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $autoResponse = DB::transaction(
            fn () => app(CreateAutoResponseAction::class)
                ->execute(AutoResponseData::fromArray($data))
        );

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

        DB::transaction(
            fn () => app(CreateMessageDeliveryAction::class)->execute(MessageDeliveryData::fromArray([
                'message_id' => $data['message_id'],
                'message_type' => $messageType,
                'delivery_id' => $autoResponse->id,
                'delivery_type' => AutoResponse::class,
                'delivery_date' => null,
            ])));

        return $autoResponse;
    }
}
