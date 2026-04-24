<?php

namespace App\Actions\LineMessage;

use App\Actions\Coupon\RedeemRewardAction;
use App\Actions\LineMessagingRequest\BuildReplyMessageRequestAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LINE\Laravel\Facades\LINEMessagingApi;

class ReplyCouponAction
{
    public function __construct(
        protected BuildReplyMessageRequestAction $buildReplyMessageRequestAction,
        protected RedeemRewardAction $redeemRewardAction,
        protected CreateTalkAction $createTalkAction
    ) {
    }

    public function execute(string $replyToken, $coupon, Friend $friend)
    {
        $text = $this->redeemRewardAction->execute($coupon->id, $friend);
        $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $text, 'text', $friend);
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
