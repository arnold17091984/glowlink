<?php

namespace App\Actions\Broadcast;

use App\Actions\MessageDelivery\MessageDeliveriesAction;
use App\Models\Friend;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;

class BroadcastMessageAction
{
    public function __construct(
        protected MessageDeliveriesAction $messageDeliveriesAction,
    ) {
    }

    public function execute(RichCard|RichVideo|RichMessage|Message $message, string $sendTo): void
    {
        $friends = Friend::all();

        foreach ($friends as $friend) {
            if ($sendTo === 'all') {
                $this->messageDeliveriesAction->execute($message, $friend);
            } elseif ($friend->mark->value === $sendTo) {
                $this->messageDeliveriesAction->execute($message, $friend);
            }
        }
    }
}
