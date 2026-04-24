<?php

namespace App\Filament\Resources\ScenarioDeliveryResource\Pages;

use App\Actions\MessageDelivery\CreateMessageDeliveryAction;
use App\Actions\ScenarioDelivery\DeleteScenarioDeliveryAction;
use App\Actions\ScenarioDelivery\EditScenarioDeliveryAction;
use App\DataTransferObjects\MessageDeliveryData;
use App\DataTransferObjects\ScenarioDeliveryData;
use App\Enums\MessageTypeEnum;
use App\Enums\ScenarioStatusEnum;
use App\Filament\Resources\ScenarioDeliveryResource;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use App\Models\ScenarioDelivery;
use DateTime;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class EditScenarioDelivery extends EditRecord
{
    protected static string $resource = ScenarioDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->using(
                function (ScenarioDelivery $record) {
                    try {
                        return app(DeleteScenarioDeliveryAction::class)->execute($record);
                    } catch (LogicException) {
                        return false;
                    }
                }
            ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $scenarioDelivery = DB::transaction(
            fn () => app(EditScenarioDeliveryAction::class)->execute(ScenarioDeliveryData::fromArray([
                'name' => $data['name'],
                'send_to' => $data['send_to'],
                'status' => ScenarioStatusEnum::PENDING->value,
            ]), $record));

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

            if (! isset($message['id'])) {
                DB::transaction(
                    fn () => app(CreateMessageDeliveryAction::class)->execute(MessageDeliveryData::fromArray([
                        'message_id' => $message['message_id'],
                        'message_type' => $messageType,
                        'delivery_id' => $scenarioDelivery->id,
                        'delivery_type' => ScenarioDelivery::class,
                        'delivery_date' => new DateTime($message['delivery_date']),
                    ])));
            } else {
                foreach ($record->messageDeliveries as $recordMessage) {
                    if ($recordMessage?->id === $message['id']) {
                        $recordMessage->update([
                            'message_id' => $message['message_id'],
                            'message_type' => $messageType,
                            'delivery_date' => new DateTime($message['delivery_date']),
                        ]);
                    }
                }
            }
        }

        $newRecord = [];
        foreach ($data['messages'] as $item) {
            if (isset($item['id'])) {
                $newRecord[] = $item['id'];
            }
        }

        $recordArray = [];
        foreach ($record->messageDeliveries as $item) {
            $recordArray[] = $item['id'];
        }

        $difference = array_diff($recordArray, $newRecord);

        foreach ($difference as $diff) {
            $deletedRecord = $record->messageDeliveries->firstWhere('id', $diff);
            $deletedRecord->delete();
        }

        return $scenarioDelivery;
    }
}
