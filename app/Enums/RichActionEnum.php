<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RichActionEnum: string implements HasLabel
{
    case LINK = 'link';
    case REFERRAL = 'referral';
    case COUPON = 'coupon';
    case MESSAGE = 'message';
    case AUTO_RESPONSE = 'auto_response';
    case NO_ACTION = 'no_action';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LINK => 'Link',
            self::REFERRAL => 'Referral',
            self::COUPON => 'Coupon',
            self::MESSAGE => 'Message',
            self::AUTO_RESPONSE => 'Auto Response',
            self::NO_ACTION => 'No Action',
        };
    }
}
