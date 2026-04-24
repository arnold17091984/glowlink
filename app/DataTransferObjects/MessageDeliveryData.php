<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use DateTime;

class MessageDeliveryData
{
    public function __construct(
        public readonly int $message_id,
        public readonly string $message_type,
        public readonly int $delivery_id,
        public readonly string $delivery_type,
        public readonly ?DateTime $delivery_date,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            message_id: (int) $data['message_id'],
            message_type: $data['message_type'],
            delivery_id: $data['delivery_id'],
            delivery_type: $data['delivery_type'],
            delivery_date: $data['delivery_date'],
        );
    }
}
