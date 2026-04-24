<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use DateTime;

class BroadcastData
{
    public function __construct(
        public readonly string $name,
        public readonly string $send_to,
        public readonly ?int $every,
        public readonly string $repeat,
        public readonly ?DateTime $start_date,
        public readonly bool $is_active,
        public readonly bool $is_send_now,
        public readonly ?DateTime $last_date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            send_to: $data['send_to'],
            every: isset($data['every']) ? (int) $data['every'] : null,
            repeat: $data['repeat'],
            start_date: isset($data['start_date']) ? new DateTime($data['start_date']) : null,
            is_active: $data['is_active'],
            is_send_now: (bool) $data['is_send_now'],
            last_date: isset($data['last_date']) ? new DateTime($data['last_date']) : null,
        );
    }
}
