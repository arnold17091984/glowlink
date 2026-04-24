<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class AutoResponseData
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_active,
        public readonly array $condition,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            is_active: $data['is_active'],
            condition: $data['condition'],
        );
    }
}
