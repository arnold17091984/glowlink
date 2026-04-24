<?php

namespace App\Actions\RichMessage;

use App\DataTransferObjects\RichMessageData;
use App\Models\RichMessage;

class CreateRichMessageAction
{
    public function execute(RichMessageData $richMessageData): RichMessage
    {
        $richMessage = RichMessage::create([
            'title' => $richMessageData->title,
            'layout_rich_message_id' => $richMessageData->layout_rich_message_id,
        ]);

        return $richMessage;
    }
}
