<?php

namespace App\Actions\Talk;

use App\Actions\LineMessage\GetMessageContentAction;
use App\Actions\Media\UploadMediaAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Enums\MessagingTypeEnum;
use App\Models\Friend;
use App\Models\User;

class StoreChatAction
{
    public function __construct(
        protected CreateTalkAction $createTalkAction,
        protected GetMessageContentAction $getMessageContentAction,
        protected UploadMediaAction $uploadMediaAction,
    ) {
    }

    public function execute($event)
    {
        $userId = $event['source']['userId'];
        $friend = Friend::whereUserId($userId)->first();

        $talk = $this->createTalkAction->execute(TalkData::fromArray([
            'sender_id' => $friend->id,
            'sender_type' => Friend::class,
            'receiver_id' => null,
            'receiver_type' => User::class,
            'message' => $event['message'],
            'flag' => FlagEnum::UNRESOLVED,
        ]
        ));

        if ($event['message']['type'] !== MessagingTypeEnum::TEXT->value) {
            $url = $this->getMessageContentAction->execute($event['message']['id']);
            $this->uploadMediaAction->execute($talk, $url);
        }

        return $talk;
    }
}
