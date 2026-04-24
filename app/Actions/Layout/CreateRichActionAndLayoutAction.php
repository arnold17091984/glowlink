<?php

namespace App\Actions\Layout;

use App\Actions\RichAction\CreateRichAction;
use App\DataTransferObjects\LayoutData;
use App\DataTransferObjects\RichActionData;
use App\Enums\RichActionEnum;
use App\Models\AutoResponse;
use App\Models\LayoutRichMessage;
use App\Models\Referral;
use App\Models\RichMessage;
use Illuminate\Support\Facades\DB;

class CreateRichActionAndLayoutAction
{
    public function execute(RichMessage $richMessage, array $data)
    {
        $layoutRichMessage = LayoutRichMessage::whereId((int) $data['selected_layout'])->first();
        $layoutIds = [];

        foreach ($layoutRichMessage['layout'] as $tab) {
            foreach ($tab as $cell) {
                $layout = DB::transaction(
                    fn () => app(CreateLayoutAction::class)->execute(
                        LayoutData::fromArray([
                            'rich_id' => $richMessage->id,
                            'rich_type' => RichMessage::class,
                            'width' => $cell['width'] ?? null,
                            'height' => $cell['height'] ?? null,
                            'offsetTop' => $cell['offsetTop'] ?? null,
                            'offsetBottom' => $cell['offsetBottom'] ?? null,
                            'offsetStart' => $cell['offsetStart'] ?? null,
                            'offsetEnd' => $cell['offsetEnd'] ?? null,
                        ]),
                    ),
                );
                $layoutIds[] = $layout->id;
            }
        }

        foreach ($data['layout'] as $index => $layoutAction) {
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
            DB::transaction(
                fn () => app(CreateRichAction::class)->execute(
                    RichActionData::fromArray([
                        'layout_id' => $layoutIds[$index],
                        'type' => $layoutAction['action'],
                        'label' => $label,
                        'link' => $link,
                        'text' => $text,
                        'model_id' => isset($layoutAction['model_id']) ? (int) $layoutAction['model_id'] : null,
                        'model_type' => $model_type,
                    ]),
                ),
            );
        }
    }
}
