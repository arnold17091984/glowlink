<?php

declare(strict_types=1);

namespace App\Enums;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum TimeEnum: string
{
    case MINUTES = 'minutes';
    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
}
