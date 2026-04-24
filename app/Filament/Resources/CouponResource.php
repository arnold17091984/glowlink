<?php

namespace App\Filament\Resources;

use App\Enums\CouponAmountTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers\FriendCouponsRelationManager;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationGroup = 'キャンペーン';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\Group::make()->schema([
                        Forms\Components\TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(3),
                    ])->columns(4)->columnSpan(4),
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Placeholder::make('Validity Period'),
                        Forms\Components\DateTimePicker::make('from')->columnSpan(2)
                            ->inlineLabel()
                            ->required(),
                    ])->columns(4)->columnSpan(4),
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Placeholder::make(''),
                        Forms\Components\DateTimePicker::make('till')
                            ->columnSpan(2)
                            ->inlineLabel()
                            ->required(),
                    ])->columnSpan(4)->columns(4),
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Placeholder::make('Amount'),
                        Forms\Components\Select::make('amount_type')
                            ->reactive()
                            ->label(trans('Type'))
                            ->options(collect(CouponAmountTypeEnum::cases())
                                ->mapWithKeys(fn (CouponAmountTypeEnum $target) => [$target->value => ucfirst($target->value)])
                                ->toArray())
                            ->required()
                            ->default(CouponAmountTypeEnum::FIXED->value),
                        Forms\Components\TextInput::make('amount')
                            ->label(function (\Filament\Forms\Get $get) {
                                if ($get('amount_type') === CouponAmountTypeEnum::PERCENTAGE->value) {
                                    return '%';
                                } elseif ($get('amount_type') === CouponAmountTypeEnum::FIXED->value) {
                                    return '¥';
                                } else {
                                    return 'Points';
                                }
                            }
                            )
                            ->numeric()
                            ->required()
                            ->maxLength(255),
                    ])->columnSpan(4)->columns(4),
                    Forms\Components\Textarea::make('description')
                        ->inlineLabel()
                        ->required()
                        ->rows(5)
                        ->columnSpan(3),
                    Forms\Components\Section::make('詳細設定')
                        ->description('抽選・使用制限・発行上限などクーポンの挙動を決める項目です。')
                        ->aside()
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->required(),
                            Forms\Components\Select::make('coupon_type')
                                ->reactive()
                                ->options(CouponTypeEnum::class)
                                ->afterStateUpdated(fn ($state, $set) => $state !== CouponTypeEnum::DISCOUNT->value && $set('required_points', ''))
                                ->required(),
                            Forms\Components\TextInput::make('required_points')
                                ->numeric()
                                ->required()
                                ->disabled(fn (\Filament\Forms\Get $get) => ($get('coupon_type') !== CouponTypeEnum::DISCOUNT->value) && ($get('coupon_type') !== CouponTypeEnum::FREE->value)),
                            Forms\Components\Toggle::make('is_edit_coupon')
                                ->reactive()
                                ->label('Edit coupon code')
                                ->required(),
                            Forms\Components\TextInput::make('coupon_code')
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->alphaNum()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($set, $state) {
                                    $set('coupon_code', strtoupper($state));
                                })
                                ->default(function () {
                                    do {
                                        $randomCode = Str::upper(Str::random(6));
                                    } while (Coupon::where('coupon_code', $randomCode)->exists());

                                    return $randomCode;
                                })
                                ->required(fn (\Filament\Forms\Get $get) => $get('is_edit_coupon'))
                                ->disabled(fn (\Filament\Forms\Get $get) => ! $get('is_edit_coupon'))
                                ->dehydrated(),
                            Forms\Components\Toggle::make('unlimited')
                                ->reactive()
                                ->label('One-time Only')
                                ->formatStateUsing(fn ($state) => ! $state)
                                ->required(),
                            Forms\Components\Group::make()->schema([
                                Forms\Components\Toggle::make('is_lottery')
                                    ->reactive()
                                    ->label('Lottery')
                                    ->required(),
                                Forms\Components\TextInput::make('win_rate')
                                    ->label(trans('Win rate %'))
                                    ->disabled(fn (\Filament\Forms\Get $get) => ! $get('is_lottery'))
                                    ->required(fn (\Filament\Forms\Get $get) => $get('is_lottery') && fn (\Filament\Forms\Get $get) => $get('is_lottery'))
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->numeric(),
                                Forms\Components\Toggle::make('is_limited')
                                    ->label('Limited')
                                    ->reactive()
                                    ->required(),
                                Forms\Components\TextInput::make('no_of_users')
                                    ->helperText('If the lottery is on, it will be based on the number of users who won the lottery.')
                                    ->disabled(fn (\Filament\Forms\Get $get) => ! $get('is_limited'))
                                    ->required(fn (\Filament\Forms\Get $get) => $get('is_limited') && fn (\Filament\Forms\Get $get) => $get('is_lottery'))
                                    ->numeric(),

                            ]),

                        ])->columnSpan(3),

                ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('from')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('till')
                    ->dateTime(),
                Tables\Columns\ImageColumn::make('image')->disk('s3'),
                Tables\Columns\IconColumn::make('is_lottery')
                    ->boolean(),
                Tables\Columns\TextColumn::make('win_rate')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_limited')
                    ->boolean(),
                Tables\Columns\TextColumn::make('no_of_users')
                    ->label('発行上限')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_edit_coupon')
                    ->label('コード編集可')
                    ->boolean(),
                Tables\Columns\TextColumn::make('coupon_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('coupon_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('有効'),
                Tables\Filters\TernaryFilter::make('is_lottery')->label('抽選'),
                Tables\Filters\TernaryFilter::make('is_limited')->label('発行上限あり'),
                Tables\Filters\SelectFilter::make('coupon_type')
                    ->label('種別')
                    ->options(CouponTypeEnum::class),
                Tables\Filters\Filter::make('validity')
                    ->label('有効期限')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('以降'),
                        Forms\Components\DatePicker::make('till')->label('以前'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('till', '>=', $d))
                            ->when($data['till'] ?? null, fn ($q, $d) => $q->whereDate('from', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->checkIfRecordIsSelectableUsing(function (Coupon $record) {
                return ! $record->isUsed();
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            FriendCouponsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
