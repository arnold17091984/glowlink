<?php

namespace App\Filament\Widgets;

use App\Models\Friend;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * 過去 30 日間の日次新規友だち推移。
 *
 * データがゼロの場合は chart.js が -1..1 のグリッドを描いて醜いので、
 * データ合計が 0 のときはダミーの最小-最大を仕込んで
 * Y 軸を 0..4 固定にし、「NO DATA」気分の静かな線を出す。
 */
class FriendGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'FRIENDS // GROWTH (LAST 30D)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected int|string|array $columnStart = ['default' => 1];

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn ($offset) => Carbon::today()->subDays($offset));

        $data = $days->map(function (Carbon $date) {
            return Friend::whereDate('created_at', $date->toDateString())->count();
        })->values()->all();

        return [
            'datasets' => [
                [
                    'label' => 'New friends',
                    'data' => $data,
                    'borderColor' => '#21D59B',
                    'backgroundColor' => 'rgba(33, 213, 155, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $days->map(fn (Carbon $d) => $d->format('m/d'))->values()->all(),
        ];
    }

    protected function getOptions(): array
    {
        $total = Friend::where('created_at', '>=', Carbon::today()->subDays(30))->count();
        $suggestedMax = $total === 0 ? 4 : null;

        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'suggestedMax' => $suggestedMax,
                    'ticks' => [
                        'color' => '#52555C',
                        'font' => [
                            'family' => "'IBM Plex Mono', ui-monospace, monospace",
                            'size' => 10,
                        ],
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.04)',
                        'drawBorder' => false,
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'color' => '#52555C',
                        'font' => [
                            'family' => "'IBM Plex Mono', ui-monospace, monospace",
                            'size' => 10,
                        ],
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'autoSkipPadding' => 24,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'titleFont' => ['family' => "'IBM Plex Mono', monospace"],
                    'bodyFont' => ['family' => "'IBM Plex Mono', monospace"],
                    'padding' => 10,
                    'displayColors' => false,
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
