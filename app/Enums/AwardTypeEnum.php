<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum AwardTypeEnum: string implements HasLabel
{
    case MANUAL = 'manual';
    case REFERRAL = 'referral';
    case REFERRAL_ACCEPTANCE = 'referral_acceptance';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::REFERRAL => 'Referral',
            self::REFERRAL_ACCEPTANCE => 'Referral Acceptance',
        };
    }
}
