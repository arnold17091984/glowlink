<?php

namespace App\Providers\Filament;

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
            ->login()
            ->registration()
            ->passwordReset()
            ->profile()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => '#21D59B',
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->brandLogo(asset('https://betrnk-tours-bucket.s3.amazonaws.com/logo/%E3%82%A2%E3%82%BB%E3%83%83%E3%83%88+1.png'))
            ->darkModeBrandLogo(asset('https://betrnk-tours-bucket.s3.amazonaws.com/logo/%E3%82%A2%E3%82%BB%E3%83%83%E3%83%88+3.png'))
            ->brandLogoHeight('3rem')
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
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\FriendStatsOverview::class,
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
