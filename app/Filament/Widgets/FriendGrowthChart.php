<?php

namespace App\Filament\Widgets;

use App\Models\Friend;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * 過去30日間の日次新規友だち推移。
 */
class FriendGrowthChart extends ChartWidget
{
    protected static ?string $heading = '友だち増加推移（過去30日）';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn ($offset) => Carbon::today()->subDays($offset));

        $data = $days->map(function (Carbon $date) {
            return Friend::whereDate('created_at', $date->toDateString())->count();
        })->values()->all();

        return [
            'datasets' => [
                [
                    'label' => '新規友だち',
                    'data' => $data,
                    'borderColor' => '#21D59B',
                    'backgroundColor' => 'rgba(33, 213, 155, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn (Carbon $d) => $d->format('m/d'))->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
