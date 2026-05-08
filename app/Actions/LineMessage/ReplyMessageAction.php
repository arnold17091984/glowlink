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
use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\LineChannel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReplyMessageAction
{
    public function __construct(
        protected BuildReplyMessageRequestAction $buildReplyMessageRequestAction,
        protected UploadMediaAction $uploadMediaAction,
        protected CreateTalkAction $createTalkAction,
        protected LineGatewayManager $gateways,
    ) {
    }

    public function execute($replyToken, Message $message, Friend $friend, $type, ?LineChannel $channel = null): Talk
    {
        if ($type === 'text') {
            $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $message['message'], $type, $friend);
        } else {
            $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $message->getFirstMediaUrl('messages'), $type, $friend);
        }
        $gateway = $channel ? $this->gateways->forChannel($channel) : $this->gateways->default();
        try {
            $gateway->reply($reply);
        } catch (\Throwable $e) {
            throw new ModelNotFoundException('Reply did not went through: '.$e->getMessage());
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
