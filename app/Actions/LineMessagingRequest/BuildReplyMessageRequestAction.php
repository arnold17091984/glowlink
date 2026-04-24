<?php

namespace App\Actions\LineMessagingRequest;

use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

class BuildReplyMessageRequestAction
{
    public function execute($replyToken, $message, string $type, $friend): ReplyMessageRequest
    {
        switch ($type) {
            case 'image':
            case 'video':
                return new ReplyMessageRequest([
                    'replyToken' => $replyToken,
                    'messages' => [
                        [
                            'type' => $type,
                            'originalContentUrl' => $message,
                            'previewImageUrl' => $message,
                        ],
                    ],
                ]);
            case 'audio':
                return new ReplyMessageRequest([
                    'replyToken' => $replyToken,
                    'messages' => [
                        [
                            'type' => $type,
                            'originalContentUrl' => $message,
                            'duration' => '300000',
                        ],
                    ],
                ]);
        }

        $points = strval($friend->points);
        $count = strval($friend->referral_count);

        $message = str_replace('[ref_points]', $points, $message);
        $message = str_replace('[ref_count]', $count, $message);

        return new ReplyMessageRequest([
            'replyToken' => $replyToken,
            'messages' => [
                [
                    'type' => $type,
                    'text' => $message,
                ],
            ],
        ]);
    }
}
