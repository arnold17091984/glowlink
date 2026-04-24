<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichMessageData
{
    public function __construct(
        public readonly string $title,
        public readonly int $layout_rich_message_id,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            layout_rich_message_id: $data['layout_rich_message_id'],
        );
    }
}
