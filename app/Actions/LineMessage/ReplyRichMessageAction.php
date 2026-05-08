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
use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\LineChannel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReplyRichMessageAction
{
    public function __construct(
        protected BuildReplyRichMessageRequestAction $buildRichReplyMessageRequestAction,
        protected UploadMediaAction $uploadMediaAction,
        protected CreateTalkAction $createTalkAction,
        protected LineGatewayManager $gateways,
    ) {
    }

    public function execute(string $replyToken, MessageDelivery $messageDelivery, $friend, ?LineChannel $channel = null): Talk
    {
        $reply = $this->buildRichReplyMessageRequestAction->execute($messageDelivery->message, $replyToken, $messageDelivery->message->layouts, $messageDelivery->message->getFirstMediaUrl('messages'), $friend);

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

        return $talk;
    }
}
