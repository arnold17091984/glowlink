<?php

namespace App\Filament\Widgets;

use App\Models\Broadcast;
use App\Models\Friend;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * ダッシュボード最上段のヒーロー挨拶 Widget。
 *
 * 日本語の時間帯別挨拶 + 曜日 + 今日の配信予定件数 + 未対応友だち件数を
 * 明朝体で静かに提示する。
 */
class HeroGreetingWidget extends Widget
{
    protected static string $view = 'filament.widgets.hero-greeting';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    protected function getViewData(): array
    {
        $now = Carbon::now()->timezone(config('app.timezone', 'Asia/Tokyo'));
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour >= 5 && $hour < 11 => 'おはようございます',
            $hour >= 11 && $hour < 17 => 'こんにちは',
            $hour >= 17 && $hour < 21 => 'こんばんは',
            default => 'お疲れさまです',
        };

        $kanji = match (true) {
            $hour >= 5 && $hour < 11 => '朝',
            $hour >= 11 && $hour < 17 => '昼',
            $hour >= 17 && $hour < 21 => '夕',
            default => '夜',
        };

        $weekdayJa = ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek];

        $user = auth()->user();
        $displayName = $user?->name ?? 'ゲスト';

        return [
            'greeting' => $greeting,
            'kanji' => $kanji,
            'name' => $displayName,
            'dateLabel' => $now->format('Y年n月j日').'（'.$weekdayJa.'）',
            'todayBroadcasts' => Broadcast::whereDate('start_date', $now->toDateString())->count(),
            'upcomingBroadcasts' => Broadcast::where('is_active', true)
                ->whereNotNull('start_date')
                ->whereBetween('start_date', [$now, $now->copy()->addDays(7)])
                ->count(),
            'friendCount' => Friend::count(),
            'friendsThisMonth' => Friend::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
        ];
    }
}
