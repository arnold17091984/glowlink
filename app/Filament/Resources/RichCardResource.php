<?php

namespace App\Filament\Resources;

use App\Enums\RichActionEnum;
use App\Enums\RichCardStyleEnum;
use App\Filament\Resources\RichCardResource\Pages;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Referral;
use App\Models\RichCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RichCardResource extends Resource
{
    protected static ?string $model = RichCard::class;

    protected static ?string $navigationGroup = 'Rich Media';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Fieldset::make('Cards')->schema([
                        Forms\Components\Repeater::make('card')->schema([
                            Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                                ->image()
                                ->imageEditor()
                                ->collection(function ($component, $state, $record) use (&$counter) {
                                    $states = $component->getContainer()->getParentComponent()->getState();

                                    $array = [];
                                    foreach ($states as $s) {
                                        $array[] = $s;
                                    }
                                    foreach ($array as $key => $data) {
                                        if (! isset($data['image'])) {
                                            $sample = explode('.', $component->getContainer()->getStatePath());

                                            return 'rich_cards_'.$sample[2];
                                        }
                                        if (isset($data['image']) && $data['image'] === $state) {
                                            return 'rich_cards_'.$key;
                                        }
                                    }
                                })
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('20:13')
                                ->acceptedFileTypes(['image/*'])
                                ->columnSpanFull()
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
                            ]),
                        ])->columnSpanFull(),
                    ])->columnSpanFull(),

                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('created_at')->date(),
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
            'index' => Pages\ListRichCards::route('/'),
            'create' => Pages\CreateRichCard::route('/create'),
            'edit' => Pages\EditRichCard::route('/{record}/edit'),
        ];
    }
}
