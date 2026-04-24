<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichMenuSetData
{
    public function __construct(
        public readonly int $layout_no,
        public readonly string $name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            layout_no: (int) $data['layout_no'],
            name: $data['name'],
        );
    }
}
