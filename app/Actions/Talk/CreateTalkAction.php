<?php

namespace App\Actions\Talk;

use App\DataTransferObjects\TalkData;
use App\Models\Talk;

class CreateTalkAction
{
    public function execute(TalkData $talkData): Talk
    {
        $talk = Talk::create([
            'sender_id' => $talkData->sender_id,
            'sender_type' => $talkData->sender_type,
            'receiver_id' => $talkData->receiver_id,
            'receiver_type' => $talkData->receiver_type,
            'message' => $talkData->message,
            'flag' => $talkData->flag,
        ]);

        return $talk;
    }
}
