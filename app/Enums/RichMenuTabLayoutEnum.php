<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\HtmlString;

// TODO: php-cs-fixer lowercase_static_reference rule should ignore cases in enum
enum RichMenuTabLayoutEnum: string implements HasLabel
{
    case NO_TAB = '1';
    case TWO_TAB = '2';
    case THREE_TAB = '3';
    case FOUR_TAB = '4';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NO_TAB => 'No Tab',
            self::TWO_TAB => '2 Tab',
            self::THREE_TAB => '3 Tab',
            self::FOUR_TAB => '4 or More Tab',
        };
    }

    public static function getDescription(): ?array
    {
        return [
            self::NO_TAB->value => new HtmlString(' <img src="'.
            asset('layout/richmenutab/no tab.svg').
            '" style="height: 100px;"  draggable="false"/>'),
            self::TWO_TAB->value => new HtmlString(' <img src="'.
            asset('layout/richmenutab/2-tabs.svg').
            '" style="height: 100px;"  draggable="false"/>'),
            self::THREE_TAB->value => new HtmlString(' <img src="'.
            asset('layout/richmenutab/3-tabs.svg').
            '" style="height: 100px;"  draggable="false"/>'),
            self::FOUR_TAB->value => new HtmlString(' <img src="'.
            asset('layout/richmenutab/4-tabs.svg').
            '" style="height: 100px;"  draggable="false"/>'),
        ];
    }
}
