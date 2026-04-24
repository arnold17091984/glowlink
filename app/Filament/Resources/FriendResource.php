<?php

namespace App\Filament\Resources;

use App\Actions\Friend\ManagePointsAction;
use App\Enums\FlagEnum;
use App\Filament\Resources\FriendResource\Pages;
use App\Filament\Resources\FriendResource\RelationManagers\AwardPointsLogsRelationManager;
use App\Models\Friend;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class FriendResource extends Resource
{
    protected static ?string $model = Friend::class;

    protected static ?string $navigationGroup = '友だち管理';

    protected static ?string $navigationLabel = '友だち';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->unique(ignoreRecord: true)
                        ->translateLabel()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('mark')
                        ->options(FlagEnum::class)
                        ->required(),
                    Forms\Components\TextInput::make('user_id')
                        ->translateLabel()
                        ->required()
                        ->maxLength(255),
                ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->translateLabel()
                    ->label('LINE User ID')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mark')
                    ->badge()
                    ->formatStateUsing(function (FlagEnum $state): string {
                        return ucfirst(str_replace('_', ' ', $state->value));
                    })
                    ->color(function ($state) {
                        $newState = str_replace(' ', '_', strtolower($state->value));

                        return match ($newState) {
                            FlagEnum::REQUIRES_ACTION->value => 'danger',
                            FlagEnum::UNRESOLVED->value => 'warning',
                            FlagEnum::ALREADY_RESOLVED->value => 'success',
                            default => 'gray',
                        };
                    })
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('referredBy.name')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('referral_count')
                    ->translateLabel()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->translateLabel()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel()
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->translateLabel()
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('mark')
                    ->label('ステータス')
                    ->options(FlagEnum::class),
                Filter::make('has_points')
                    ->label('ポイント保持者のみ')
                    ->query(fn ($query) => $query->where('points', '>', 0))
                    ->toggle(),
                Filter::make('has_referral')
                    ->label('紹介経由の友だち')
                    ->query(fn ($query) => $query->whereNotNull('referred_by'))
                    ->toggle(),
                Filter::make('registered_on')
                    ->label('登録日')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('以降'),
                        Forms\Components\DatePicker::make('until')->label('以前'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from']  ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Manage points')
                    ->form([
                        // ...
                        Forms\Components\Placeholder::make('')->content(function ($record) {
                            return new HtmlString(
                                '<div style="width: 100%; display:flex; flex-direction:column; align-items:center; justify-content: center; gap:20px">
                                <img src="'.($record->profile_url ?? 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQGhmTe4FGFtGAgbIwVBxoD3FmED3E5EE99UGPItI0xnQ&s').'" style="max-height: 200px; border-radius: 100%; "/>
                                <p style="font-weight:bold; font-size: 25px;">'.$record->name.'</p>
                                </div>'
                            );
                        }),
                        Forms\Components\TextInput::make('points')
                            ->label('Adjust Points')
                            ->translateLabel()
                            ->required()
                            ->numeric(),
                        Forms\Components\Textarea::make('reason')
                            ->translateLabel(),
                    ])
                    ->action(function (array $data, $record): void {
                        // ...
                        app(ManagePointsAction::class)->execute($data, $record);
                    })
                    ->slideOver()->modalWidth('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('updateMark')
                        ->label('ステータスを一括変更')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Forms\Components\Select::make('mark')
                                ->label('ステータス')
                                ->options(FlagEnum::class)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['mark' => $data['mark']]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            AwardPointsLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFriends::route('/'),
            'create' => Pages\CreateFriend::route('/create'),
            'edit' => Pages\EditFriend::route('/{record}/edit'),
        ];
    }
}
