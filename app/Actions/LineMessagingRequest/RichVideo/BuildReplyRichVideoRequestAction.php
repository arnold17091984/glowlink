<?php

namespace App\Actions\LineMessagingRequest\RichVideo;

use App\Enums\RichActionEnum;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Friend;
use App\Models\Referral;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

class BuildReplyRichVideoRequestAction
{
    public function execute(string $replyToken, $richVideo, Friend $friend): ReplyMessageRequest
    {
        $videoUrl = $richVideo->getFirstMediaUrl('rich_videos');
        $imageUrl = $richVideo->getFirstMediaUrl('rich_video_thumbnails');
        $contents = [];

        $data = [
            'type' => 'bubble',
            'hero' => [
                'type' => 'video',
                'url' => $videoUrl,
                'previewUrl' => $imageUrl,
                'altContent' => [
                    'type' => 'image',
                    'size' => 'full',
                    'aspectRatio' => '20:13',
                    'aspectMode' => 'cover',
                    'url' => $imageUrl,
                ],
                'aspectRatio' => '20:13',
            ],
        ];

        if (! is_null($richVideo->title) || ! is_null($richVideo->description)) {
            $data['body'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'contents' => [],
            ];

            if (! is_null($richVideo->title)) {
                $data['body']['contents'][] = [
                    'type' => 'text',
                    'text' => $richVideo->title,
                    'size' => 'xl',
                    'weight' => 'bold',
                    'style' => 'normal',
                ];
            }

            if (! is_null($richVideo->description)) {
                $data['body']['contents'][] = [
                    'type' => 'text',
                    'text' => $richVideo->description,
                    'wrap' => true,
                    'size' => 'sm',
                ];
            }
        }

        if (! empty($richVideo->button)) {
            foreach ($richVideo->button as $button) {
                $buttons = [
                    'type' => 'button',
                    'style' => $button['style'],
                    'color' => $button['color'],
                    'margin' => 'sm',
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

                $contents[] = $buttons;
            }

            $data['footer'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $contents,
            ];
        }

        return new ReplyMessageRequest([
            'replyToken' => $replyToken,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => 'This is a Video Message',
                    'contents' => $data,
                ],
            ],
        ]);
    }
}
