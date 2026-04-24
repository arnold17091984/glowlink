<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichVideoData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?array $button,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            button: $data['button'] ?? null,
        );
    }
}
