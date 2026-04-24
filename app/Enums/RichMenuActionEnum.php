<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RichMenuActionEnum: string implements HasLabel
{
    case LINK = 'link';
    case MESSAGE = 'message';
    case SUB_MENU = 'sub_menu';
    case AUTO_RESPONSE = 'auto_response';
    case NO_ACTION = 'no_action';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LINK => 'Link',
            self::MESSAGE => 'Message',
            self::SUB_MENU => 'Sub Rich Menu',
            self::AUTO_RESPONSE => 'Auto Response',
            self::NO_ACTION => 'No Action',
        };
    }
}
