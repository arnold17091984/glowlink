<?php

declare(strict_types=1);

namespace App\Enums;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum CouponAmountTypeEnum: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case POINTS = 'points';
}
