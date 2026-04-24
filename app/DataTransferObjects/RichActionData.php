<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichActionData
{
    public function __construct(
        public readonly ?int $model_id,
        public readonly ?string $model_type,
        public readonly int $layout_id,
        public readonly string $type,
        public readonly ?string $label,
        public readonly ?string $link,
        public readonly ?string $text,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            model_id: $data['model_id'] ?? null,
            model_type: $data['model_type'] ?? null,
            layout_id: $data['layout_id'],
            type: $data['type'],
            label: $data['label'] ?? null,
            link: $data['link'] ?? null,
            text: $data['text'] ?? null,

        );
    }
}
