<?php

namespace App\Actions\LineMessagingRequest\RichMessage;

use App\Enums\RichActionEnum;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Layout;
use App\Models\Referral;
use App\Models\RichMessage;
use Illuminate\Database\Eloquent\Collection;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;

class BuildPushRichMessageRequestAction
{
    public function execute(RichMessage $richMessage, string $userId, Layout|Collection $layouts, string $imageUrl): PushMessageRequest
    {
        $data = [
            [
                'type' => 'image',
                'url' => $imageUrl,
                'size' => 'full',
                'aspectMode' => 'cover',
                'aspectRatio' => '1:1',
                'gravity' => 'center',
            ],
        ];

        foreach ($layouts as $layout) {
            if ($layout->richAction['type'] === RichActionEnum::MESSAGE->value) {
                $action = [
                    'type' => 'message',
                    'label' => 'action',
                    'text' => $layout->richAction['text'],
                ];
            }

            if ($layout->richAction['type'] === RichActionEnum::AUTO_RESPONSE->value) {
                $autoResponse = AutoResponse::find($layout->richAction['model_id']);
                $action = [
                    'type' => 'message',
                    'label' => 'action',
                    'text' => $autoResponse->condition[0]['keyword'] ?? '',
                ];
            }

            if ($layout->richAction['type'] === RichActionEnum::LINK->value) {
                $action = [
                    'type' => 'uri',
                    'label' => 'action',
                    'uri' => $layout->richAction['link'],
                ];
            }

            if ($layout->richAction['type'] === RichActionEnum::COUPON->value) {
                $coupon = Coupon::find($layout->richAction['model_id']);
                $couponCode = rawurlencode($coupon->coupon_code);
                $action = [
                    'type' => 'uri',
                    'label' => 'action',
                    'uri' => env('LINE_LIFF_REDEEM').'?couponCode='.$couponCode,
                ];
            }

            if ($layout->richAction['type'] === RichActionEnum::REFERRAL->value) {
                $referral = Referral::find($layout->richAction['model_id']);
                $referralName = rawurlencode($referral->name);
                $action = [
                    'type' => 'uri',
                    'label' => 'action',
                    'uri' => $referral->link.'?referral='.$userId.'&redirect='.env('LINE_LINK').'&referralName='.$referralName,
                ];
            }

            $try = [
                'contents' => [],
                'type' => 'box',
                'layout' => 'horizontal',
                'flex' => 1,
                'position' => 'absolute',
            ];

            if ($layout->richAction['type'] !== RichActionEnum::NO_ACTION->value) {
                $try['action'] = $action;
            }

            if (! is_null($layout['offsetBottom'])) {
                $try['offsetBottom'] = $layout['offsetBottom'];
            }

            if (! is_null($layout['offsetStart'])) {
                $try['offsetStart'] = $layout['offsetStart'];
            }

            if (! is_null($layout['offsetTop'])) {
                $try['offsetTop'] = $layout['offsetTop'];
            }

            if (! is_null($layout['offsetEnd'])) {
                $try['offsetEnd'] = $layout['offsetEnd'];
            }

            if (! is_null($layout['width'])) {
                $try['width'] = $layout['width'];
            }

            if (! is_null($layout['height'])) {
                $try['height'] = $layout['height'];
            }

            $data[] = $try;
        }

        return new PushMessageRequest([
            'to' => $userId,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => $richMessage->title,
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'paddingAll' => '0px',
                            'contents' => $data,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
