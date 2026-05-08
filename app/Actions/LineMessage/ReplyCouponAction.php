<?php

namespace App\Actions\LineMessage;

use App\Actions\Coupon\RedeemRewardAction;
use App\Actions\LineMessagingRequest\BuildReplyMessageRequestAction;
use App\Actions\Talk\CreateTalkAction;
use App\DataTransferObjects\TalkData;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\User;
use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\LineChannel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReplyCouponAction
{
    public function __construct(
        protected BuildReplyMessageRequestAction $buildReplyMessageRequestAction,
        protected RedeemRewardAction $redeemRewardAction,
        protected CreateTalkAction $createTalkAction,
        protected LineGatewayManager $gateways,
    ) {
    }

    public function execute(string $replyToken, $coupon, Friend $friend, ?LineChannel $channel = null)
    {
        $text = $this->redeemRewardAction->execute($coupon->id, $friend);
        $reply = $this->buildReplyMessageRequestAction->execute($replyToken, $text, 'text', $friend);
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
