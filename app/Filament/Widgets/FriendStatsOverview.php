<?php

namespace App\Filament\Widgets;

use App\Models\Broadcast;
use App\Models\Friend;
use App\Models\FriendCoupon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * マーケター向けトップ指標。
 *
 * - 友だち総数・今月の純増
 * - 本日配信済みブロードキャスト数
 * - 当月クーポン利用率 (redeemed / issued)
 * - 紹介経由の新規友だち (当月)
 *
 * 集計はすべてキャッシュ可。Friend 数が大きくなった段階で Cache::remember を噛ませる。
 */
class FriendStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $totalFriends = Friend::count();
        $monthlyNewFriends = Friend::where('created_at', '>=', $startOfMonth)->count();
        $todayBroadcasts = Broadcast::whereDate('start_date', $now->toDateString())->count();

        $issuedThisMonth = FriendCoupon::where('created_at', '>=', $startOfMonth)->count();
        $redeemedThisMonth = FriendCoupon::where('created_at', '>=', $startOfMonth)
            ->whereIn('status', ['won', 'unlimited'])
            ->count();
        $redeemRate = $issuedThisMonth > 0
            ? round(($redeemedThisMonth / $issuedThisMonth) * 100, 1)
            : 0;

        $referralNew = Friend::where('created_at', '>=', $startOfMonth)
            ->whereNotNull('referred_by')
            ->count();

        return [
            Stat::make('友だち総数', number_format($totalFriends))
                ->description('今月 +'.number_format($monthlyNewFriends))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color($monthlyNewFriends > 0 ? 'success' : 'gray')
                ->chart($this->friendGrowthSparkline()),

            Stat::make('本日の配信', number_format($todayBroadcasts))
                ->description('予定日=本日')
                ->descriptionIcon('heroicon-o-paper-airplane')
                ->color($todayBroadcasts > 0 ? 'primary' : 'gray'),

            Stat::make('クーポン利用率', $redeemRate.'%')
                ->description("今月 {$redeemedThisMonth} / {$issuedThisMonth} 件")
                ->descriptionIcon('heroicon-o-ticket')
                ->color($redeemRate >= 30 ? 'success' : ($redeemRate >= 10 ? 'warning' : 'danger')),

            Stat::make('紹介成立', number_format($referralNew))
                ->description('今月 紹介経由の新規')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($referralNew > 0 ? 'success' : 'gray'),
        ];
    }

    /**
     * 過去7日間の日次新規友だち数で sparkline を構築。
     */
    private function friendGrowthSparkline(): array
    {
        $days = collect(range(6, 0))->map(fn ($offset) => Carbon::today()->subDays($offset));

        return $days->map(fn (Carbon $date) => Friend::whereDate('created_at', $date->toDateString())->count())
            ->values()
            ->all();
    }
}
