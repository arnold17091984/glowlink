<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RichCardStyleEnum: string implements HasLabel
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case LINK = 'link';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PRIMARY => 'Primary',
            self::SECONDARY => 'Secondary',
            self::LINK => 'Link',
        };
    }
}
