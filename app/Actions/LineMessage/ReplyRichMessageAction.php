<?php

namespace App\Actions\LineMessage;

use App\Actions\LineMessagingRequest\RichMessage\BuildReplyRichMessageRequestAction;
use App\Actions\Media\UploadMediaAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\MessageDelivery;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Laravel\Facades\LINEMessagingApi;

class ReplyRichMessageAction
{
    public function __construct(
        protected BuildReplyRichMessageRequestAction $buildRichReplyMessageRequestAction,
        protected UploadMediaAction $uploadMediaAction,
        protected CreateTalkAction $createTalkAction
    ) {
    }

    public function execute(string $replyToken, MessageDelivery $messageDelivery, $friend): Talk
    {
        $reply = $this->buildRichReplyMessageRequestAction->execute($messageDelivery->message, $replyToken, $messageDelivery->message->layouts, $messageDelivery->message->getFirstMediaUrl('messages'), $friend);

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
