<?php

namespace App\Filament\Resources\RichMessageResource\Pages;

use App\Actions\Layout\CreateRichActionAndLayoutAction;
use App\Actions\RichAction\EditRichAction;
use App\Actions\RichMessage\EditRichMessageAction;
use App\DataTransferObjects\RichActionData;
use App\DataTransferObjects\RichMessageData;
use App\Enums\RichActionEnum;
use App\Filament\Resources\RichMessageResource;
use App\Models\AutoResponse;
use App\Models\Layout;
use App\Models\Referral;
use App\Models\RichMessage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditRichMessage extends EditRecord
{
    protected static string $resource = RichMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['selected_layout'] = $data['selected_layout'] ?? 1;
        $layouts = Layout::whereRichId($record->id)
            ->whereRichType(RichMessage::class)
            ->get();

        $record->update([
            'title' => $data['title'],
        ]);

        if ($record->layout_rich_message_id != (int) $data['selected_layout']) {
            foreach ($layouts as $layout) {
                $layout->richAction->delete();
                $layout->delete();
            }
            $richMessage = DB::transaction(
                fn () => app(EditRichMessageAction::class)->execute(
                    RichMessageData::fromArray([
                        'title' => $this->data['title'],
                        'layout_rich_message_id' => (int) $data['selected_layout'],
                    ]),
                    $record,
                ),
            );

            app(CreateRichActionAndLayoutAction::class)->execute($richMessage, $this->data);
        } else {
            $layoutIds = [];
            $richActions = [];
            foreach ($layouts as $layout) {
                $layoutIds[] = $layout->id;
                $richActions[] = $layout->richAction;
            }

            foreach ($this->data['layout'] as $index => $layoutAction) {

                $model_type = '';
                $link = $layoutAction['link'] ?? null;
                $text = $layoutAction['text'] ?? null;
                $label = $layoutAction['label'] ?? null;

                if ($layoutAction['action'] === RichActionEnum::NO_ACTION->value) {
                    $model_type = null;
                    $link = null;
                    $text = null;
                    $label = null;
                }

                if ($layoutAction['action'] === RichActionEnum::AUTO_RESPONSE->value) {
                    $model_type = AutoResponse::class;
                    $link = null;
                    $text = null;
                    $label = null;
                }
                if ($layoutAction['action'] === RichActionEnum::REFERRAL->value) {
                    $model_type = Referral::class;
                    $link = null;
                    $text = null;
                    $label = null;
                }

                app(EditRichAction::class)->execute(
                    RichActionData::fromArray([
                        'layout_id' => $layoutIds[$index],
                        'type' => $layoutAction['action'],
                        'label' => $label,
                        'link' => $link,
                        'text' => $text,
                        'model_id' => isset($layoutAction['model_id']) ? (int) $layoutAction['model_id'] : null,
                        'model_type' => $model_type,
                    ]),
                    $richActions[$index],
                );
            }
        }

        return $record;
    }
}
