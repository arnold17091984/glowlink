<?php

namespace App\Filament\Resources;

use App\Enums\FlagEnum;
use App\Enums\MessagingTypeEnum;
use App\Enums\UserType;
use App\Filament\Resources\TalkResource\Pages;
use App\Models\Friend;
use App\Models\Talk;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TalkResource extends Resource
{
    protected static ?string $model = Talk::class;

    protected static ?string $navigationGroup = 'Friend Management';

    protected static ?string $recordTitleAttribute = 'receiver.name';

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->message['text'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$model::whereReadAt(null)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message Details')->schema([
                    // Select::make('receiver')
                    //     ->label('Select Friend')
                    //     ->options(Friend::all()->pluck('name', 'id'))

                    //     ->placeholder(function ($record) {
                    //         return $record->receiver->name ?? auth()->user()->name;
                    //     }),
                    Forms\Components\TextInput::make('sender.name')
                        ->label('Sender Name')
                        ->formatStateUsing(function ($record) {
                            return $record->sender->name ?? 'System Administrator';
                        }),
                    Forms\Components\TextInput::make('receiver.name')
                        ->label('Reciever Name')
                        ->formatStateUsing(function ($record) {
                            return $record->receiver->name ?? auth()->user()->name;
                        }),
                    Forms\Components\TextInput::make('message.type')
                        ->label(trans('Type')),
                    Forms\Components\TextInput::make('flag'),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Recieved at'),
                    Forms\Components\DateTimePicker::make('read_at'),
                ])
                    ->columns(2)
                    ->columnspan(2),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Message')->schema([
                        Forms\Components\Textarea::make('message.text')
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('message.type') != MessagingTypeEnum::TEXT->value)
                            ->columnSpanFull()
                            ->rows(8)
                            ->required(),
                        Forms\Components\Placeholder::make('file')
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('message.type') == MessagingTypeEnum::TEXT->value)
                            ->columnSpanFull()
                            ->formatStateUsing(function ($record) {
                                if ($record) {
                                    return $record->media[0] ?? null;
                                }
                            })
                            ->content(
                                function ($state, \Filament\Forms\Get $get) {
                                    if (! is_null($state)) {
                                        switch ($get('message.type')) {
                                            case MessagingTypeEnum::IMAGE->value:
                                                return new HtmlString('<img src="'.$state['original_url'].'" style="border-radius: 8px"/>');
                                                break;
                                            case MessagingTypeEnum::VIDEO->value:
                                                return new HtmlString(
                                                    '<div class="video-player">
                                                    <video controls style="border-radius: 8px">
                                                        <source src="'.$state['original_url'].'" type="video/mp4 ">
                                                        Your browser does not support the video element.
                                                    </video>
                                                </div>'
                                                );
                                                break;
                                            case MessagingTypeEnum::AUDIO->value:
                                                return new HtmlString(
                                                    '<div>
                                                    <audio controls>
                                                        <source src="'.$state['original_url'].'" type="audio/mpeg">
                                                        Your browser does not support the audio element.
                                                    </audio>
                                                </div>'
                                                );
                                                break;
                                        }
                                    }
                                }
                            ),
                    ]),
                ])
                    ->columnspan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->state(function ($record) {
                        return $record->sender->name ?? 'System Administrator';
                    })
                    ->label(trans('Sender Name')),
                Tables\Columns\TextColumn::make('sender_type')
                    ->formatStateUsing(function ($state): string {
                        $newState = '';
                        if ($state === Friend::class) {
                            $newState = UserType::FRIEND->value;
                        }
                        if ($state === User::class) {
                            $newState = UserType::ADMIN->value;
                        }

                        return ucfirst($newState);
                    })
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('message.type')
                    ->translateLabel()
                    ->label('Message Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('receiver.name')
                    ->state(function ($record) {
                        return $record->receiver->name ?? auth()->user()->name;
                    })
                    ->label(trans('Receiver Name')),
                Tables\Columns\TextColumn::make('receiver_type')
                    ->formatStateUsing(function ($state): string {
                        $newState = '';
                        if ($state === Friend::class) {
                            $newState = UserType::FRIEND->value;
                        }
                        if ($state === User::class) {
                            $newState = UserType::ADMIN->value;
                        }

                        return ucfirst($newState);
                    })
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('flag')
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
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('flag')
                    ->options(collect(FlagEnum::cases())
                        ->mapWithKeys(fn (FlagEnum $target) => [$target->value => Str::headline($target->value)])
                        ->toArray()),
                SelectFilter::make('sender_type')
                    ->options([
                        User::class => ucfirst(UserType::ADMIN->value),
                        Friend::class => ucfirst(UserType::FRIEND->value),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->translateLabel()
                    ->form([
                        Select::make('flag')
                            ->translateLabel()
                            ->options(
                                collect(FlagEnum::cases())
                                    ->mapWithKeys(fn (FlagEnum $target) => [$target->value => Str::headline($target->value)])
                                    ->toArray()
                            )
                            ->required(),
                    ])
                    ->action(function (array $data, Talk $record): void {
                        $record->update([
                            'flag' => $data['flag'],
                        ]);
                        $record->save();
                    }),

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
            'index' => Pages\ListTalks::route('/'),
            'create' => Pages\CreateTalk::route('/create'),
            'edit' => Pages\EditTalk::route('/{record}/edit'),
            'view' => Pages\ViewTalks::route('/{record}'),
        ];
    }
}
