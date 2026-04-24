<?php

namespace App\Filament\Widgets;

use App\Models\Broadcast;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * 今後7日間に配信予定のブロードキャスト一覧。
 */
class UpcomingBroadcastsTable extends BaseWidget
{
    protected static ?string $heading = '今後7日間の配信予定';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Broadcast::query()
                    ->with(['messageDelivery.message'])
                    ->where('is_active', true)
                    ->whereNotNull('start_date')
                    ->whereBetween('start_date', [now(), now()->addDays(7)])
                    ->orderBy('start_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('配信名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('配信予定日')
                    ->dateTime('m/d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('send_to')
                    ->label('対象')
                    ->badge(),
                Tables\Columns\TextColumn::make('repeat')
                    ->label('繰り返し')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean(),
            ])
            ->emptyStateHeading('今後7日間に配信予定のブロードキャストはありません')
            ->emptyStateIcon('heroicon-o-paper-airplane')
            ->paginated([5, 10, 25]);
    }
}
