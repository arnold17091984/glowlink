<?php

declare(strict_types=1);

namespace App\Enums;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum UsedForEnum: string
{
    case AUTO_RESPONSE = 'auto_response';
    case SCENARIO_DELIVERY = 'scenario_delivery';
    case BROADCAST = 'broadcast';
}
