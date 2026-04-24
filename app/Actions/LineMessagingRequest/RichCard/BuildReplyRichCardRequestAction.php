<?php

namespace App\Actions\LineMessagingRequest\RichCard;

use App\Enums\RichActionEnum;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Friend;
use App\Models\Referral;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

class BuildReplyRichCardRequestAction
{
    public function execute(string $replyToken, $richCard, Friend $friend): ReplyMessageRequest
    {
        $messageContent = [];
        foreach ($richCard['card'] as $key => $card) {
            $data = [
                'type' => 'bubble',
                'size' => 'kilo',
                'hero' => [
                    'type' => 'image',
                    'size' => 'full',
                    'aspectMode' => 'cover',
                    'url' => $richCard->getFirstMediaUrl('rich_cards_'.$key),
                    'aspectRatio' => '20:13',
                ],
            ];

            if (! is_null($card['title']) || ! is_null($card['description'])) {
                $data['body'] = [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => [],
                ];

                if (! is_null($card['title'])) {
                    $data['body']['contents'][] = [
                        'type' => 'text',
                        'text' => $card['title'],
                        'size' => 'lg',
                        'weight' => 'bold',
                        'style' => 'normal',
                    ];
                }

                if (! is_null($card['description'])) {
                    $data['body']['contents'][] = [
                        'type' => 'text',
                        'text' => $card['description'],
                        'wrap' => true,
                        'size' => 'sm',
                    ];
                }
            }

            if (! empty($card['button'])) {
                $buttonContents = [];
                foreach ($card['button'] as $button) {
                    $buttons = [
                        'type' => 'button',
                        'style' => $button['style'],
                        'color' => $button['color'],
                        // 'adjustMode' => 'shrink-to-fit',
                        // 'scaling ' => true,
                        'margin' => 'sm',
                        'height' => 'sm',
                    ];

                    if ($button['action'] === RichActionEnum::MESSAGE->value) {
                        $action = [
                            'type' => 'message',
                            'label' => $button['title'],
                            'text' => $button['message'],
                        ];
                    }

                    if ($button['action'] === RichActionEnum::LINK->value) {
                        $action = [
                            'type' => 'uri',
                            'label' => $button['title'],
                            'uri' => $button['link'],
                        ];
                    }

                    if ($button['action'] === RichActionEnum::AUTO_RESPONSE->value) {
                        $autoResponse = AutoResponse::find($button['auto_response_id']);
                        $action = [
                            'type' => 'message',
                            'label' => $button['title'],
                            'text' => $autoResponse->condition[0]['keyword'] ?? '',
                        ];
                    }

                    if ($button['action'] === RichActionEnum::COUPON->value) {
                        $coupon = Coupon::find($button['coupon_id']);
                        $couponCode = rawurlencode($coupon->coupon_code);

                        $action = [
                            'type' => 'uri',
                            'label' => $button['title'],
                            'uri' => env('LINE_LIFF_REDEEM').'?couponCode='.$couponCode,
                        ];
                    }

                    if ($button['action'] === RichActionEnum::REFERRAL->value) {
                        $referral = Referral::find($button['referral_id']);
                        $referralName = rawurlencode($referral->name);
                        $action = [
                            'type' => 'uri',
                            'label' => $button['title'],
                            'uri' => $referral->link.'?referral='.$friend->user_id.'&redirect='.env('LINE_LINK').'&referralName='.$referralName,
                        ];
                    }

                    if ($button['action'] !== RichActionEnum::NO_ACTION->value) {
                        $buttons['action'] = $action;
                    }

                    $buttonContents[] = $buttons;
                }

                $data['footer'] = [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $buttonContents,
                ];
            }

            $messageContent[] = $data;
        }

        $contents = [
            'type' => 'carousel',
            'contents' => $messageContent,
        ];

        return new ReplyMessageRequest([
            'replyToken' => $replyToken,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => 'This is a Rich Card Message',
                    'contents' => $contents,
                ],
            ],
        ]);
    }
}
