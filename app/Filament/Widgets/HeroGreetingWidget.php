<?php

namespace App\Filament\Widgets;

use App\Models\Broadcast;
use App\Models\Friend;
use App\Models\FriendCoupon;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * MISSION STATUS — ダッシュボード最上段のオペレータ挨拶 × 系統監視。
 *
 * 表示要素:
 *   - "SYSTEM ONLINE" + ライブクロック (JST)
 *   - オペレータ名 (ログイン中) + 時間帯ラベル (DAY / NIGHT / SHIFT等)
 *   - 4 セル KPI: 友だち総数 / 本日配信 / 未処理ジョブ / LINE API 状態
 */
class HeroGreetingWidget extends Widget
{
    protected static string $view = 'filament.widgets.hero-greeting';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -100;

    protected function getViewData(): array
    {
        $now = Carbon::now()->timezone(config('app.timezone', 'Asia/Tokyo'));
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour >= 5 && $hour < 11 => 'Good morning',
            $hour >= 11 && $hour < 17 => 'Good afternoon',
            $hour >= 17 && $hour < 21 => 'Good evening',
            default => 'Standby',
        };

        $shiftTag = match (true) {
            $hour >= 5 && $hour < 12 => 'MORNING OPS',
            $hour >= 12 && $hour < 17 => 'DAY OPS',
            $hour >= 17 && $hour < 21 => 'EVENING OPS',
            default => 'NIGHT WATCH',
        };

        $user = auth()->user();
        $operator = $user?->name ?? 'GUEST';

        // Queue 深度 (Redis)
        $queueDepth = 0;
        try {
            $queueDepth = (int) Redis::connection()->llen('queues:broadcasts')
                        + (int) Redis::connection()->llen('queues:scenarios')
                        + (int) Redis::connection()->llen('queues:default');
        } catch (\Throwable) {
            $queueDepth = 0;
        }

        // DB 接続可否
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbOk = false;
        }

        $lineApiOk = ! empty(config('line-bot.channel_access_token'));

        $friendCount = Friend::count();
        $friendsThisMonth = Friend::where('created_at', '>=', $now->copy()->startOfMonth())->count();
        $broadcastsToday = Broadcast::whereDate('start_date', $now->toDateString())->count();
        $couponsRedeemedToday = FriendCoupon::whereIn('status', ['won', 'unlimited'])
            ->whereDate('updated_at', $now->toDateString())
            ->count();

        return [
            'greeting' => $greeting,
            'operator' => $operator,
            'shiftTag' => $shiftTag,
            'isoDate' => $now->format('Y-m-d'),
            'isoTime' => $now->format('H:i:s'),
            'timezone' => $now->format('T'),
            'friendCount' => $friendCount,
            'friendsThisMonth' => $friendsThisMonth,
            'broadcastsToday' => $broadcastsToday,
            'couponsRedeemedToday' => $couponsRedeemedToday,
            'queueDepth' => $queueDepth,
            'lineApiOk' => $lineApiOk,
            'dbOk' => $dbOk,
        ];
    }
}
