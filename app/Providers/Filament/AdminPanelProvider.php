<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->profile()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Stone,
                'info' => Color::Sky,
                'primary' => [
                    50  => '#EEFCF6',
                    100 => '#D3F8E8',
                    200 => '#A4F0D2',
                    300 => '#6DE4B7',
                    400 => '#3FDAA4',
                    500 => '#21D59B',
                    600 => '#15B584',
                    700 => '#0E8F6A',
                    800 => '#0B7055',
                    900 => '#095C46',
                    950 => '#04382B',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('IBM Plex Sans JP')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Dark)
            ->brandName('glowlink')
            ->brandLogo(fn () => view('filament.brand-mark'))
            ->darkModeBrandLogo(fn () => view('filament.brand-mark'))
            ->brandLogoHeight('1.5rem')
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make('Friend Management')
                    ->label('友だち管理')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('Messaging')
                    ->label('メッセージ')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis'),
                NavigationGroup::make('Outreach')
                    ->label('キャンペーン')
                    ->icon('heroicon-o-building-storefront'),
                NavigationGroup::make('Rich Media')
                    ->label('リッチコンテンツ')
                    ->icon('heroicon-o-photo'),
                NavigationGroup::make('Utilities')
                    ->label('設定・ユーティリティ')
                    ->icon('heroicon-o-rectangle-group'),
                NavigationGroup::make('チャネル接続')
                    ->label('チャネル接続')
                    ->icon('heroicon-o-link'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Hero が KPI 4 セルを兼ねるので、従来の FriendStatsOverview は
                // 重複になるため外す。必要なら個別サブページで復活させる。
                \App\Filament\Widgets\HeroGreetingWidget::class,
                \App\Filament\Widgets\FriendGrowthChart::class,
                \App\Filament\Widgets\UpcomingBroadcastsTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
