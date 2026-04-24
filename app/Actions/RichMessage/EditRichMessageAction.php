<?php

namespace App\Actions\RichMessage;

use App\DataTransferObjects\RichMessageData;
use App\Models\RichMessage;

class EditRichMessageAction
{
    public function execute(RichMessageData $richMessageData, RichMessage $richMessage): RichMessage
    {
        $richMessage->update([
            'title' => $richMessageData->title,
            'layout_rich_message_id' => $richMessageData->layout_rich_message_id,
        ]);

        return $richMessage;
    }
}
