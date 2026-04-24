<?php

namespace App\Actions\RichMessage;

use LINE\Clients\MessagingApi\Model\FlexBubble;

class BuildFlexBubbleAction
{
    public function execute(): FlexBubble
    {
        $cell = [
            'type' => 'box',
            'layout' => 'horizontal',
            'contents' => [],
            'flex' => 1,
            'offsetBottom' => 'none',
            'offsetStart' => 'none',
            'action' => [
                'type' => 'uri',
                'label' => 'action',
                'uri' => 'http://linecorp.com/',
            ],
            'position' => 'absolute',
            'offsetTop' => 'none',
            'offsetEnd' => 'none',
        ];

        return new FlexBubble([
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'image',
                        'url' => 'https://scdn.line-apps.com/n/channel_devcenter/img/flexsnapshot/clip/clip3.jpg',
                        'size' => 'full',
                        'aspectMode' => 'cover',
                        'aspectRatio' => '1:1',
                        'gravity' => 'center',
                    ],

                    'paddingAll' => '0px',
                ],
            ],
        ]);
    }
}
