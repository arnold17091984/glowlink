<?php

namespace App\Actions\AutoResponse;

use App\Actions\LineMessage\ReplyCouponAction;
use App\Actions\LineMessage\ReplyMessageAction;
use App\Actions\LineMessage\ReplyRichCardAction;
use App\Actions\LineMessage\ReplyRichMessageAction;
use App\Actions\LineMessage\ReplyRichVideoAction;
use App\Enums\MessagingTypeEnum;
use App\Models\AutoResponse;
use App\Models\Friend;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;

class AutoResponseAction
{
    public function __construct(
        protected ReplyCouponAction $replyCouponAction,
        protected ReplyMessageAction $replyMessageAction,
        protected ReplyRichMessageAction $replyRichMessageAction,
        protected ReplyRichVideoAction $replyRichVideoAction,
        protected ReplyRichCardAction $replyRichCardAction
    ) {}

    public function execute($event)
    {
        $message = $event['message'];

        if ($message['type'] !== MessagingTypeEnum::TEXT->value) {
            return;
        }

        $autoresponse = AutoResponse::whereIsActive(true)->get();

        $friend = Friend::whereUserId($event['source']['userId'])->first();

        foreach ($autoresponse as $response) {
            $messageDelivery = $response->messageDelivery;
            foreach ($response->condition as $condition) {
                if ($condition['is_perfect_match']) {
                    if (strtolower($message['text']) === strtolower($condition['keyword'])) {
                        $reply = null;
                        switch (true) {
                            case app($messageDelivery->message_type) instanceof Message:
                                $reply = $this->replyMessageAction->execute($event['replyToken'], $messageDelivery->message, $friend, $messageDelivery->message['type']->value);
                                break;
                            case app($messageDelivery->message_type) instanceof RichMessage:
                                $reply = $this->replyRichMessageAction->execute($event['replyToken'], $messageDelivery, $friend);
                                break;
                            case app($messageDelivery->message_type) instanceof RichVideo:
                                $reply = $this->replyRichVideoAction->execute($event['replyToken'], $messageDelivery, $friend);
                                break;
                            case app($messageDelivery->message_type) instanceof RichCard:
                                $reply = $this->replyRichCardAction->execute($event['replyToken'], $messageDelivery, $friend);
                                break;
                        }

                        return $reply;
                    }
                } else {
                    $keywords = explode(' ', $condition['keyword']);
                    $message = explode(' ', $message['text']);

                    $keywords_lower = array_map('strtolower', $keywords);
                    $message_lower = array_map('strtolower', $message);

                    $commonWords = array_intersect($keywords_lower, $message_lower);

                    if (count($commonWords) >= $condition['no_of_word']) {
                        $reply = $this->replyMessageAction->execute($event['replyToken'], $messageDelivery->message, $friend, $messageDelivery->message['type']->value);

                        return $reply;
                    }
                }
            }
        }

        return $event;
    }
}
