<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubRichMenuActionEnum: string implements HasLabel
{
    case LINK = 'link';
    case MESSAGE = 'message';
    case BACK_TO_MAIN = 'back_to_main';
    case AUTO_RESPONSE = 'auto_response';
    case NO_ACTION = 'no_action';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LINK => 'Link',
            self::MESSAGE => 'Message',
            self::BACK_TO_MAIN => 'Back to main menu',
            self::AUTO_RESPONSE => 'Auto Response',
            self::NO_ACTION => 'No Action',
        };
    }
}
