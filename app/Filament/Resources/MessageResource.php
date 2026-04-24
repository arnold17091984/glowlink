<?php

namespace App\Filament\Resources;

use App\Enums\MessagingTypeEnum;
use App\Enums\UsedForEnum;
use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 1;

    public ?string $type = null;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Details')->schema([
                        Forms\Components\TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Select::make('type')
                                ->reactive()
                                ->options(collect(MessagingTypeEnum::cases())
                                    ->mapWithKeys(function (MessagingTypeEnum $target) {
                                        return [$target->value => ucfirst($target->value)];
                                    })
                                    ->toArray())
                                ->default('text')
                                // ->columnSpanFull()
                                ->required(),
                            Forms\Components\Select::make('used_for')
                                ->options(collect(UsedForEnum::cases())
                                    ->mapWithKeys(fn (UsedForEnum $target) => [$target->value => ucfirst(str_replace('_', ' ', $target->value))])
                                    ->toArray())
                                // ->columnSpanFull()
                                ->required(),
                        ])->columns(2),
                        // ]),
                        // ])->columnSpan(2),
                        // Forms\Components\Section::make('Message')->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('type') === MessagingTypeEnum::TEXT->value)
                            // ->acceptedFileTypes(
                            //     function (\Filament\Forms\Get $get){
                            //       if($get('type') === MessagingTypeEnum::AUDIO->value){
                            //         return  ['audio/*'];
                            //       }
                            //       elseif($get('type') === MessagingTypeEnum::IMAGE->value){
                            //         return ['image/*'];
                            //       }
                            //       elseif($get('type') === MessagingTypeEnum::VIDEO->value){
                            //         dd($get('type'));
                            //        return ['video/*'];
                            //       }
                            //       return $type;
                            //     }
                            // )
                            ->collection('messages')
                            ->disk(env('MEDIA_DISK'))
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Text Message')
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('type') != MessagingTypeEnum::TEXT->value)
                            ->columnSpanFull()
                            ->rows(8)
                            ->required(),
                    ]),
                ])->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('message')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('used_for')
                    ->formatStateUsing(function ($state) {
                        return ucfirst(str_replace('_', ' ', $state->value));
                    }),
                Tables\Columns\IconColumn::make('messageDeliveries')
                    ->label(trans('Used'))
                    ->icon(
                        fn (Model $record): string => match ($record->messageDeliveries()->exists()) {
                            true => 'heroicon-o-check-badge',
                            false => 'heroicon-o-x-circle',
                        })
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->default(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
                SelectFilter::make('used_for')
                    ->options(collect(UsedForEnum::cases())
                        ->mapWithKeys(fn (UsedForEnum $target) => [$target->value => ucfirst(str_replace('_', ' ', $target->value))])),
            ])
            ->actions([
                Tables\Actions\Action::make('activities')->url(fn ($record) => MessageResource::getUrl('activities', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                function (Model $record) {
                    return ! $record->messageDeliveries()->exists();
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
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
            'activities' => Pages\ListMessageActivities::route('/{record}/activities'),
        ];
    }
}
