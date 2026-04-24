<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return 'オペレーション';
    }

    public function getHeading(): string
    {
        return 'オペレーション';
    }

    public static function getNavigationLabel(): string
    {
        return 'ダッシュボード';
    }
}
