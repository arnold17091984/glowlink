<?php

declare(strict_types=1);

namespace App\Enums;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum ScenarioStatusEnum: string
{
    case ONGOING = 'ongoing';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
}
