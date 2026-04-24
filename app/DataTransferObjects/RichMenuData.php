<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class RichMenuData
{
    public function __construct(
        public readonly int $rich_menu_set_id,
        public readonly ?string $chatbar_text,
        public readonly string $rich_menu_id,
        public readonly string $name,
        public readonly string $rich_menu_alias,
        public readonly string $tab_no,
        public readonly bool $selected,
        public readonly int $selected_layout,
        public readonly array $actions,
        public array $image,
        public readonly int $width,
        public readonly int $height,
        public readonly array $areas,
    ) {
    }

    public static function fromArray(array $data, array $areas, int $richSetId, $richMenuAliasId): self
    {
        return new self(
            rich_menu_set_id: (int) $richSetId,
            name: $data['name'],
            rich_menu_id: strtolower($richMenuAliasId.'-richmenu-'.($data['tab_no'])),
            rich_menu_alias: $richMenuAliasId,
            chatbar_text: $data['chatbar_text'] ?? 'Menu',
            tab_no: (string) $data['tab_no'],
            selected: $data['selected'],
            selected_layout: (int) $data['selected_layout'],
            actions: $data['actions'],
            image: $data['image'],
            width: 1280,
            height: 863,
            areas: $areas,
        );
    }
}
