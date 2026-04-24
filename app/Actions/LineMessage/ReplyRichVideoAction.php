<?php

namespace App\Actions\LineMessage;

use App\Actions\LineMessagingRequest\RichVideo\BuildReplyRichVideoRequestAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\MessageDelivery;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Laravel\Facades\LINEMessagingApi;

class ReplyRichVideoAction
{
    public function __construct(
        protected BuildReplyRichVideoRequestAction $buildRichReplyVideoRequestAction,
        protected CreateTalkAction $createTalkAction
    ) {}

    public function execute(string $replyToken, MessageDelivery $messageDelivery, $friend): Talk
    {
        $reply = $this->buildRichReplyVideoRequestAction->execute($replyToken, $messageDelivery->message, $friend);

        $response = LINEMessagingApi::replyMessage($reply);

        if (! $response) {
            throw new ModelNotFoundException('Reply did not went through');
        }

        $talk = $this->createTalkAction->execute(TalkData::fromArray([
            'sender_id' => null,
            'sender_type' => User::class,
            'receiver_id' => $friend->id,
            'receiver_type' => Friend::class,
            'message' => $reply['messages'][0],
            'flag' => FlagEnum::ADMIN,
        ]));

        return $talk;
    }
}
