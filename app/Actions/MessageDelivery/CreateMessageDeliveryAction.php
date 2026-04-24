<?php

namespace App\Actions\MessageDelivery;

use App\DataTransferObjects\MessageDeliveryData;
use App\Models\MessageDelivery;

class CreateMessageDeliveryAction
{
    public function execute(MessageDeliveryData $messageDeliveryData): MessageDelivery
    {
        $messageDelivery = MessageDelivery::create([
            'message_id' => $messageDeliveryData->message_id,
            'message_type' => $messageDeliveryData->message_type,
            'delivery_id' => $messageDeliveryData->delivery_id,
            'delivery_type' => $messageDeliveryData->delivery_type,
            'delivery_date' => $messageDeliveryData->delivery_date,
        ]);

        return $messageDelivery;
    }
}
