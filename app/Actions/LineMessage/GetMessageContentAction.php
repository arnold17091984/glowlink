<?php

namespace App\Actions\LineMessage;

use LINE\Laravel\Facades\LINEMessagingBlobApi;

class GetMessageContentAction
{
    public function execute(int $messageId)
    {
        $response = LINEMessagingBlobApi::getMessageContent($messageId);

        return $response->getRealPath();
    }
}
