<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class ScenarioDeliveryData
{
    public function __construct(
        public readonly string $name,
        public readonly string $send_to,
        public readonly ?string $status,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            send_to: $data['send_to'],
            status: $data['status'] ?? null,
        );
    }
}
