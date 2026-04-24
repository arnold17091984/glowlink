<?php

namespace App\Actions\LineMessage;

use App\Actions\LineMessagingRequest\BuildReplyMessageRequestAction;
use App\Actions\Media\UploadMediaAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\Message;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Laravel\Facades\LINEMessagingApi;

class ReplyMessageAction
{
    public function __construct(
        protected BuildReplyMessageRequestAction $buildReplyMessageRequestAction,
        protected UploadMediaAction $uploadMediaAction,
        protected CreateTalkAction $createTalkAction
    ) {
    }

    public function execute($replyToken, Message $message, Friend $friend, $type): Talk
    {
        if ($type === 'text') {
            $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $message['message'], $type, $friend);
        } else {
            $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $message->getFirstMediaUrl('messages'), $type, $friend);
        }
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

        if ($type !== 'text') {
            $mediaItem = $message->getMedia('messages')->first();
            $mediaItem->copy($talk, 'talk', env('MEDIA_DISK'));
        }

        return $talk;
    }
}
