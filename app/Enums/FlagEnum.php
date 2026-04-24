<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FlagEnum: string implements HasLabel
{
    case UNRESOLVED = 'unresolved';
    case REQUIRES_ACTION = 'requires_action';
    case ALREADY_RESOLVED = 'already_resolved';
    case ADMIN = 'admin';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UNRESOLVED => 'Unresolved',
            self::REQUIRES_ACTION => 'Requires Action',
            self::ALREADY_RESOLVED => 'Already Resolved',
            self::ADMIN => 'Admin',
        };
    }
}
