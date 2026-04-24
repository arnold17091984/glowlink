<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Actions\Broadcast\DeleteBroadcastAction;
use App\Actions\Broadcast\EditBroadcastAction;
use App\Console\Commands\BroadcastCommand;
use App\DataTransferObjects\BroadcastData;
use App\Enums\RepeatEnum;
use App\Filament\Resources\BroadcastResource;
use App\Models\Broadcast;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class EditBroadcast extends EditRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->using(
                function (Broadcast $record) {
                    try {
                        return app(DeleteBroadcastAction::class)->execute($record);
                    } catch (LogicException) {
                        return false;
                    }
                }
            ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($data['is_send_now']) {
            $data['start_date'] = now()->toDateTimeString();

            if ($data['repeat'] === RepeatEnum::ONCE->value) {
                $data['is_active'] = false;
            }
        }

        if ($data['is_active'] === false) {
            $data['last_date'] = now()->toDateTimeString();
        }

        $broadcast = app(EditBroadcastAction::class)->execute(BroadcastData::fromArray($data), $record);

        app(BroadcastCommand::class)->updateNextDate($broadcast);

        $record->messageDelivery->message_id = $data['message_id'];

        $record->messageDelivery->save();

        return $broadcast;
    }

    protected function afterSave(): void
    {
        redirect()->route('filament.admin.resources.broadcasts.view', ['record' => $this->record]);
    }
}
