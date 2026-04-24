<?php

namespace App\Filament\Resources\FriendResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AwardPointsLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'awardPointsLogs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('referral.name'),
                Forms\Components\TextInput::make('awarded_points'),
                Forms\Components\TextInput::make('type'),
                Forms\Components\Textarea::make('reason'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('friend_id')
            ->columns([
                Tables\Columns\TextColumn::make('referral.name'),
                Tables\Columns\TextColumn::make('awarded_points')->words(5)->wrap(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('reason'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([

            ]);
    }
}
