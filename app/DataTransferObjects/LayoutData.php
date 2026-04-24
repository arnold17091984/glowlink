<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class LayoutData
{
    public function __construct(
        public readonly int $rich_id,
        public readonly string $rich_type,
        public readonly ?float $x,
        public readonly ?float $y,
        public readonly ?string $width,
        public readonly ?string $height,
        public readonly ?string $offsetTop,
        public readonly ?string $offsetBottom,
        public readonly ?string $offsetStart,
        public readonly ?string $offsetEnd,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rich_id: $data['rich_id'],
            rich_type: $data['rich_type'],
            x: $data['x'] ?? null,
            y: $data['y'] ?? null,
            width: $data['width'] ?? null,
            height: $data['height'] ?? null,
            offsetTop: $data['offsetTop'] ?? null,
            offsetBottom: $data['offsetBottom'] ?? null,
            offsetStart: $data['offsetStart'] ?? null,
            offsetEnd: $data['offsetEnd'] ?? null,
        );
    }
}
