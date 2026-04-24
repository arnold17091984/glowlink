<?php

namespace App\Filament\Resources\RichMessageResource\Pages;

use App\Actions\Layout\CreateRichActionAndLayoutAction;
use App\Actions\RichMessage\CreateRichMessageAction;
use App\DataTransferObjects\RichMessageData;
use App\Filament\Resources\RichMessageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRichMessage extends CreateRecord
{
    protected static string $resource = RichMessageResource::class;

    public $selectedTab = 1;

    public function onClickSelectedLayout($path)
    {
        $this->data['selected_layout'] = (int) $this->selectedTab;

        $actionCounts = [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 4, 6 => 3, 7 => 3, 8 => 6, 9 => 2, 10 => 3, 11 => 3, 12 => 3];

        $selected_layout = $this->data['selected_layout'] ?? null;
        $action = $selected_layout ? array_fill(0, $actionCounts[$selected_layout], ['action' => null]) : [];

        $this->data['layout'] = $action;

    }

    public function onClickClose($path)
    {
        $this->selectedTab = $this->data['selected_layout'];
    }

    public function handleRecordCreation(array $data): Model
    {

        $data['selected_layout'] = $data['selected_layout'] ?? 1;
        $richMessage = DB::transaction(
            fn () => app(CreateRichMessageAction::class)->execute(
                RichMessageData::fromArray([
                    'title' => $this->data['title'],
                    'layout_rich_message_id' => (int) $data['selected_layout'],
                ]),
            ),
        );

        $action = new CreateRichActionAndLayoutAction();

        $action->execute($richMessage, $data);

        return $richMessage;
    }
}
