<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RedeemCouponStatusEnum: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case USED = 'used';
    case UNLIMITED = 'unlimited';
    case NOT_WON = 'not_won';
    case WON = 'won';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::USED => 'Used',
            self::UNLIMITED => 'Unlimited',
            self::NOT_WON => 'Not Won',
            self::WON => 'Won',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::UNLIMITED => 'warning',
            self::USED => 'success',
            self::NOT_WON => 'danger',
            self::WON => 'success',
        };
    }
}
