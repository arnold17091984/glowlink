<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?string $navigationGroup = 'キャンペーン';

    protected static ?string $navigationLabel = '紹介キャンペーン';

    protected static ?string  = '紹介キャンペーン';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->columnSpan(2)
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Textarea::make('message')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($set, $state) {
                            $message = rawurlencode($state);

                            $lineAction = 'line://msg/text/'.$message.'%0A'.env('LINE_LIFF_REFERRAL');

                            $set('link', $lineAction);
                        })
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('link')
                        ->required()
                        ->readOnly()
                        ->columnSpanFull(),
                ])->columns(1)->columnSpan(2),
                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(trans('Active'))
                        ->required(),
                    Forms\Components\TextInput::make('referrer_awarded_points')
                        ->label(trans('Referrer Awarded Points'))
                        ->required()
                        ->columnSpan(2)
                        ->numeric(),
                    Forms\Components\TextInput::make('referral_acceptance_points')
                        ->label(trans('Referral Acceptance Points'))
                        ->required()
                        ->columnSpan(2)
                        ->numeric(),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(trans('Active'))
                    ->boolean()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->checkIfRecordIsSelectableUsing(function (Referral $record) {
                return ! $record->isUsed();
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->checkIfRecordIsSelectableUsing(
                function (Referral $record) {
                    return ! $record->richAction()->exists();
                },
            );
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
        ];
    }
}
