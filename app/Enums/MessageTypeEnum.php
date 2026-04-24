<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MessageTypeEnum: string implements HasLabel
{
    case MESSAGE = 'message';
    case RICH_MESSAGE = 'rich_message';
    case RICH_VIDEO = 'rich_video';
    case RICH_CARD = 'rich_card';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MESSAGE => 'Message',
            self::RICH_MESSAGE => 'Rich Message',
            self::RICH_VIDEO => 'Rich Video',
            self::RICH_CARD => 'Rich Card',
        };
    }
}
