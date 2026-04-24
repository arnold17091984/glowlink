<?php

namespace App\Actions\LineMessagingRequest;

use LINE\Clients\MessagingApi\Model\PushMessageRequest;

class BuildPushMessageRequestAction
{
    public function execute(string $userId, string $message, string $type): PushMessageRequest
    {
        switch ($type) {
            case 'image':
            case 'video':
                return new PushMessageRequest([
                    'to' => $userId,
                    'messages' => [
                        [
                            'type' => $type,
                            'originalContentUrl' => $message,
                            'previewImageUrl' => $message,
                        ],
                    ],
                ]);
            case 'audio':
                return new PushMessageRequest([
                    'to' => $userId,
                    'messages' => [
                        [
                            'type' => $type,
                            'originalContentUrl' => $message,
                            'duration' => '300000',
                        ],
                    ],
                ]);
        }

        return new PushMessageRequest([
            'to' => $userId,
            'messages' => [
                [
                    'type' => $type,
                    'text' => $message,
                ],
            ],
        ]);
    }
}
