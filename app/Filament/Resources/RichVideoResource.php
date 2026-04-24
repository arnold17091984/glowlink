<?php

namespace App\Filament\Resources;

use App\Enums\RichActionEnum;
use App\Enums\RichCardStyleEnum;
use App\Filament\Resources\RichVideoResource\Pages;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Referral;
use App\Models\RichVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RichVideoResource extends Resource
{
    protected static ?string $model = RichVideo::class;

    protected static ?string $navigationGroup = 'リッチコンテンツ';

    protected static ?string $navigationLabel = 'リッチビデオ';

    protected static ?string  = 'リッチビデオ';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('video')
                        ->acceptedFileTypes(['video/*'])
                        ->columnSpan(1)
                        ->collection('rich_videos')
                        ->required(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                        ->label('Thumbnail')
                        ->image()
                        ->imageEditor()
                        ->collection('rich_video_thumbnails')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('20:13')
                        ->acceptedFileTypes(['image/*'])
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                    Forms\Components\Fieldset::make('Buttons')->schema([
                        Forms\Components\Repeater::make('button')->schema([
                            Forms\Components\TextInput::make('title')
                                ->required(),

                            Forms\Components\Select::make('style')
                                ->options(RichCardStyleEnum::class)
                                ->default(RichCardStyleEnum::PRIMARY)
                                ->required(),
                            Forms\Components\ColorPicker::make('color')
                                ->default('#22c55e')
                                ->required(),
                            Forms\Components\Select::make('action')
                                ->reactive()
                                ->required()
                                ->options(RichActionEnum::class),
                            Forms\Components\Select::make('auto_response_id')
                                ->label('Auto Response')
                                ->reactive()
                                ->columnSpanFull()
                                ->required()
                                ->visible(fn (Get $get) => $get('action') === RichActionEnum::AUTO_RESPONSE->value)
                                ->options(function () {
                                    return AutoResponse::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                })
                                ->afterStateUpdated(
                                    function (Set $set, $state) {
                                        $autoResponse = AutoResponse::find($state);
                                        $set('message', $autoResponse->condition[0]['keyword'] ?? '');
                                    }
                                ),
                            Forms\Components\Select::make('coupon_id')
                                ->label('Coupon')
                                ->reactive()
                                ->columnSpanFull()
                                ->required()
                                ->visible(fn (Get $get) => $get('action') === RichActionEnum::COUPON->value)
                                ->options(function () {
                                    return Coupon::whereIsActive(true)->orderBy('updated_at', 'desc')->pluck('name', 'id');
                                })
                                ->afterStateUpdated(
                                    function (Get $get, $state, Set $set) {
                                        if ($get('action') === RichActionEnum::COUPON->value) {
                                            $coupon = Coupon::find($state);
                                            $couponCode = rawurlencode($coupon->coupon_code);
                                            $set('link', env('LINE_LIFF_REDEEM').'?couponCode='.$couponCode);
                                        }
                                    }
                                ),

                            Forms\Components\Select::make('referral_id')
                                ->label('Referral')
                                ->reactive()
                                ->columnSpanFull()
                                ->required()
                                ->visible(fn (Get $get) => $get('action') === RichActionEnum::REFERRAL->value)
                                ->options(function () {
                                    return Referral::whereIsActive(true)->orderBy('updated_at', 'desc')->pluck('name', 'id');
                                })
                                ->afterStateUpdated(
                                    function (Get $get, $state, Set $set) {
                                        if ($get('action') === RichActionEnum::REFERRAL->value) {
                                            $referral = Referral::find($state);
                                            $set('text', $referral->message ?? null);
                                            $set('link', $referral->link ?? null);
                                        }
                                    }
                                ),
                            Forms\Components\Textarea::make('message')
                                ->readonly(fn (Get $get) => $get('action') === RichActionEnum::AUTO_RESPONSE->value)
                                ->visible(fn (Get $get) => $get('action') === RichActionEnum::MESSAGE->value)
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('link')
                                ->visible(fn (Get $get) => $get('action') === RichActionEnum::LINK->value)
                                ->required()
                                ->columnSpanFull(),
                        ])
                            ->defaultItems(0)
                            ->reorderableWithDragAndDrop(false)
                            ->collapsible()
                            ->columns(2)
                            ->columnSpanFull()
                            ->hiddenLabel(),
                    ])
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRichVideos::route('/'),
            'create' => Pages\CreateRichVideo::route('/create'),
            'edit' => Pages\EditRichVideo::route('/{record}/edit'),
        ];
    }
}
