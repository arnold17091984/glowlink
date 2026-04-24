<?php

namespace App\Filament\Resources\ScenarioDeliveryResource\Pages;

use App\Actions\MessageDelivery\CreateMessageDeliveryAction;
use App\Actions\ScenarioDelivery\CreateScenarioDeliveryAction;
use App\DataTransferObjects\MessageDeliveryData;
use App\DataTransferObjects\ScenarioDeliveryData;
use App\Enums\MessageTypeEnum;
use App\Filament\Resources\ScenarioDeliveryResource;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use App\Models\ScenarioDelivery;
use DateTime;
use DB;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateScenarioDelivery extends CreateRecord
{
    protected static string $resource = ScenarioDeliveryResource::class;

    public function handleRecordCreation(array $data): Model
    {
        $scenarioDelivery = DB::transaction(fn () => app(CreateScenarioDeliveryAction::class)->execute(ScenarioDeliveryData::fromArray($data)));

        foreach ($data['messages'] as $message) {
            switch ($message['message_type']) {
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
                fn () => app(CreateMessageDeliveryAction::class)->execute(
                    MessageDeliveryData::fromArray([
                        'message_id' => $message['message_id'],
                        'message_type' => $messageType,
                        'delivery_id' => $scenarioDelivery->id,
                        'delivery_type' => ScenarioDelivery::class,
                        'delivery_date' => new DateTime($message['delivery_date']),
                    ]),
                ),
            );
        }

        return $scenarioDelivery;
    }
}
