<?php

namespace App\Actions\MessageDelivery;

use App\Actions\LineMessagingRequest\BuildPushMessageRequestAction;
use App\Actions\LineMessagingRequest\RichCard\BuildPushRichCardRequestAction;
use App\Actions\LineMessagingRequest\RichMessage\BuildPushRichMessageRequestAction;
use App\Actions\LineMessagingRequest\RichVideo\BuildPushRichVideoRequestAction;
use App\Actions\Media\UploadMediaAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Enums\MessagingTypeEnum;
use App\Models\Friend;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Laravel\Facades\LINEMessagingApi;

class MessageDeliveriesAction
{
    public ?MessagingTypeEnum $type = null;

    public function __construct(
        protected CreateTalkAction $createTalkAction,
        protected BuildPushRichCardRequestAction $buildPushRichCardRequestAction,
        protected BuildPushRichVideoRequestAction $buildPushRichVideoRequestAction,
        protected BuildPushRichMessageRequestAction $buildPushRichMessageRequestAction,
        protected BuildPushMessageRequestAction $buildPushMessageRequestAction,
        protected UploadMediaAction $uploadMediaAction,
        protected PushMessageRequest $push,
    ) {}

    public function execute(RichCard|RichVideo|RichMessage|Message $message, Friend $friend): Talk
    {
        switch (true) {
            case $message instanceof Message:
                $this->type = $message->type;

                if ($this->type === MessagingTypeEnum::TEXT) {
                    $this->push = $this->buildPushMessageRequestAction->execute(
                        $friend->user_id,
                        $message['message'],
                        $this->type->value
                    );
                } else {
                    $this->push = $this->buildPushMessageRequestAction->execute(
                        $friend->user_id,
                        $message->getFirstMediaUrl('messages'),
                        $this->type->value
                    );
                }
                break;

            case $message instanceof RichMessage:
                $this->push = $this->buildPushRichMessageRequestAction->execute(
                    $message,
                    $friend->user_id,
                    $message->layouts,
                    $message->getFirstMediaUrl('messages')
                );
                break;

            case $message instanceof RichVideo:
                $this->push = $this->buildPushRichVideoRequestAction->execute(
                    $friend->user_id,
                    $message,
                    $friend
                );
                break;

            case $message instanceof RichCard:
                $this->push = $this->buildPushRichCardRequestAction->execute(
                    $friend->user_id,
                    $message,
                    $friend
                );
                break;
        }

        $talk = $this->createTalkAction->execute(TalkData::fromArray([
            'sender_id' => null,
            'sender_type' => User::class,
            'receiver_id' => $friend->id,
            'receiver_type' => Friend::class,
            'message' => $this->push->getMessages()[0],
            'flag' => FlagEnum::ADMIN,
        ]));

        if ($message instanceof Message && ($this->type !== MessagingTypeEnum::TEXT || null)) {
            $mediaItem = $message->getMedia('messages')->first();
            $mediaItem->copy($talk, 'talk', env('MEDIA_DISK'));
        }

        $response = LINEMessagingApi::pushMessage($this->push);

        if (! $response) {
            throw new ModelNotFoundException('message not go through');
        }

        return $talk;
    }
}
