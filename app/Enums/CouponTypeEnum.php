<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum CouponTypeEnum: string implements HasLabel
{
    case DISCOUNT = 'discount';
    case FREE = 'free';
    case Gift = 'gift';
    case CASHBACK = 'cashback';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DISCOUNT => 'Discount',
            self::FREE => 'Free',
            self::Gift => 'Gift',
            self::CASHBACK => 'Cashback',
            self::OTHER => 'Other',
        };
    }
}
