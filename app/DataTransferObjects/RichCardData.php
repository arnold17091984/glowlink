<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichCardData
{
    public function __construct(
        public readonly string $name,
        public readonly ?array $card,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            card: $data['card'] ?? null,
        );
    }
}
