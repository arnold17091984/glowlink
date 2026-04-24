<?php

namespace App\Filament\Resources;

use App\Actions\AutoResponse\DeleteAutoResponseAction;
use App\Enums\MessageTypeEnum;
use App\Enums\MessagingTypeEnum;
use App\Enums\UsedForEnum;
use App\Filament\Resources\AutoResponseResource\Pages;
use App\Models\AutoResponse;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use LogicException;

class AutoResponseResource extends Resource
{
    protected static ?string $model = AutoResponse::class;

    protected static ?string $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                self::getAutoResponseDetails()
                    ->columns(2)
                    ->columnSpan(2),
                self::getPreview(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('activities')->url(fn ($record) => AutoResponseResource::getUrl('activities', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->checkIfRecordIsSelectableUsing(function (AutoResponse $record) {
                return ! $record->isUsed();
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->using(function (Collection $records) {
                        try {
                            foreach ($records as $record) {
                                app(DeleteAutoResponseAction::class)->execute($record);
                            }
                        } catch (LogicException) {
                            return false;
                        }
                    }),
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
            'index' => Pages\ListAutoResponses::route('/'),
            'create' => Pages\CreateAutoResponse::route('/create'),
            'edit' => Pages\EditAutoResponse::route('/{record}/edit'),
            'activities' => Pages\ListAutoResponseActivities::route('/{record}/activities'),
        ];
    }

    public static function getAutoResponseDetails(): Forms\Components\Component
    {
        return Forms\Components\Section::make('Details')->schema([
            Forms\Components\TextInput::make('name')
                ->unique(ignoreRecord: true)
                ->translateLabel()
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('message_type')
                ->required()
                ->options(MessageTypeEnum::class)
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        switch (true) {
                            case $record->messageDelivery->message instanceof Message:
                                return MessageTypeEnum::MESSAGE->value;
                                break;
                            case $record->messageDelivery->message instanceof RichMessage:
                                return MessageTypeEnum::RICH_MESSAGE->value;
                                break;
                            case $record->messageDelivery->message instanceof RichVideo:
                                return MessageTypeEnum::RICH_VIDEO->value;
                                break;
                            case $record->messageDelivery->message instanceof RichCard:
                                return MessageTypeEnum::RICH_CARD->value;
                                break;
                        }
                    }
                })
                ->afterStateUpdated(function ($set) {
                    $set('message_id', null);
                })
                ->reactive(),
            Forms\Components\Select::make('message_id')
                // ->hidden(fn ($get) => $get('message_type') != MessageTypeEnum::MESSAGE->value)
                ->disabled(fn ($get) => empty($get('message_type')))
                ->required()
                ->reactive()
                ->columnSpanFull()
                ->options(
                    function ($get) {
                        switch ($get('message_type')) {
                            case MessageTypeEnum::MESSAGE->value:
                                return Message::whereUsedFor(UsedForEnum::AUTO_RESPONSE)->orderBy('updated_at', 'desc')->pluck('name', 'id');
                                break;
                            case MessageTypeEnum::RICH_MESSAGE->value:
                                return RichMessage::orderBy('updated_at', 'desc')->pluck('title', 'id');
                                break;
                            case MessageTypeEnum::RICH_VIDEO->value:
                                return RichVideo::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                break;
                            case MessageTypeEnum::RICH_CARD->value:
                                return RichCard::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                break;
                        }
                    }
                )
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message_id;
                    }
                })
                ->afterStateUpdated(
                    function (callable $set, $get, ?string $state) {
                        $message = null;
                        if ($get('message_type') === MessageTypeEnum::MESSAGE->value) {
                            $message = Message::find($state);
                            $file = $message->message != null ? null : $message->getFirstMedia('messages');
                            $set('message_name', $message->name ?? '');
                            $set('messaging_type', $message->type ?? '');
                            $set('message', $message->message ?? '');
                            $set('file', $file ?? '');
                        }
                        if ($get('message_type') === MessageTypeEnum::RICH_MESSAGE->value) {
                            $richMessage = RichMessage::find($state);
                            $set('rich_message_title', $richMessage->title ?? '');
                        }
                    }
                ),
            Forms\Components\Fieldset::make('Condition')->schema([
                Forms\Components\Repeater::make('condition')
                    ->hiddenLabel()
                    ->schema([
                        Forms\Components\Textarea::make('keyword')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('no_of_word')
                            ->label(trans('No. of word matches'))
                            ->required(fn (\Filament\Forms\Get $get) => ! $get('is_perfect_match'))
                            ->numeric()
                            ->columnSpan(2)
                            ->disabled(fn (\Filament\Forms\Get $get) => $get('is_perfect_match')),
                        Forms\Components\Toggle::make('is_perfect_match')->label(trans('Perfect match'))
                            ->required()
                            ->inline(false)
                            ->default(true)
                            ->reactive(),
                    ])
                    ->columns(3)
                    ->columnSpan(2)
                    ->hidden(fn (\Filament\Forms\Get $get) => $get('is_all_match')),
                Forms\Components\Textarea::make('keyword')
                    ->required()
                    ->hidden(fn (\Filament\Forms\Get $get) => ! $get('is_all_match')
                    )->columnSpan(2),
            ])->columns(2)
                ->columnSpan(2),
        ]);
    }

    public static function getPreview(): Forms\Components\Component
    {
        return Forms\Components\Group::make()->schema([
            self::status(),

            self::getMessagePreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::MESSAGE->value),

            self::getRichMessagePreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::RICH_MESSAGE->value),
        ]);
    }

    public static function getMessagePreview(): Forms\Components\Component
    {
        return Forms\Components\Section::make('Message Preview')->schema([
            Forms\Components\TextInput::make('message_name')
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->name ?? null;
                    }
                })
                ->label('Name')
                ->disabled(),
            Forms\Components\TextInput::make('messaging_type')
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->type ?? null;
                    }
                })
                ->label('Type')
                ->disabled(),
            Forms\Components\Textarea::make('message')
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->message ?? null;
                    }
                })
                ->label('Message')
                ->disabled(),
            Forms\Components\Placeholder::make('file')
                ->hidden(fn (\Filament\Forms\Get $get) => $get('messaging_type') == MessagingTypeEnum::TEXT || $get('messaging_type') == null)
                ->formatStateUsing(function ($record, $get) {

                    if ($record && ! is_null(($get('messaging_type')))) {
                        return $record->messageDelivery->message->getFirstMedia('messages');
                    }
                })
                ->content(
                    function ($state, \Filament\Forms\Get $get) {
                        if (! is_null($state)) {
                            switch ($get('messaging_type')) {
                                case MessagingTypeEnum::IMAGE:
                                    return new HtmlString('<img src="'.$state->getUrl().'" style="max-height: 400px; border-radius: 8px"/>');
                                    break;
                                case MessagingTypeEnum::VIDEO:
                                    return new HtmlString(
                                        '<div class="video-player">
                                            <video controls style="max-height: 400px; border-radius: 8px">
                                                <source src="'.$state->getUrl().'" type="video/mp4 ">
                                                Your browser does not support the video element.
                                            </video>
                                        </div>'
                                    );
                                    break;
                                case MessagingTypeEnum::AUDIO:
                                    return new HtmlString(
                                        '<div>
                                            <audio controls style="max-height: 30px">
                                                <source src="'.$state->getUrl().'" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>'
                                    );
                                    break;
                            }
                        }
                    }
                ),
        ]);
    }

    public static function getRichMessagePreview(): Forms\Components\Component
    {
        return Forms\Components\Group::make()->schema([
            Forms\Components\Section::make('Rich Message Preview')->schema([
                Forms\Components\TextInput::make('rich_message_title')
                    ->formatStateUsing(function ($record) {
                        if ($record) {
                            return $record->messageDelivery->message->title;
                        }
                    })
                    ->label('Title')
                    ->disabled(),
            ]),
            Forms\Components\Placeholder::make('')->content(function ($get) {
                $richMessage = RichMessage::find($get('message_id'));

                return new HtmlString(
                    '<div>
                    <img src="'.$richMessage->getFirstMediaUrl('messages').'" style="max-height: 400px; border-radius: 8px; "/>
                </div>'
                );
            })->hidden(fn ($get) => empty($get('message_id'))),
        ]);
    }

    public static function status(): Forms\Components\Component
    {
        return Forms\Components\Section::make('Status')->schema([
            Forms\Components\Toggle::make('is_active')
                ->label(trans('Active'))
                ->default(true)
                ->required()->columns(2),
        ]);
    }
}
