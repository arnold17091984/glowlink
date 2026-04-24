<?php

namespace App\Filament\Resources;

use App\Actions\ScenarioDelivery\DeleteScenarioDeliveryAction;
use App\Enums\FlagEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\MessagingTypeEnum;
use App\Enums\ScenarioStatusEnum;
use App\Enums\UsedForEnum;
use App\Filament\Resources\ScenarioDeliveryResource\Pages;
use App\Filament\Rules\DateTimeRules;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use App\Models\ScenarioDelivery;
use Carbon\Carbon;
use DateTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use LogicException;

class ScenarioDeliveryResource extends Resource
{
    protected static ?string $model = ScenarioDelivery::class;

    protected static ?string $navigationGroup = 'メッセージ';

    protected static ?string $navigationLabel = 'シナリオ配信';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Forms\Components\Select::make('send_to')
                        ->label('Send To')
                        ->options(collect(FlagEnum::cases())
                            ->mapWithKeys(fn (FlagEnum $target) => ['all' => 'All', $target->value => ucfirst(str_replace('_', ' ', $target->value))]))
                        ->required(),
                    Forms\Components\TextInput::make('status')
                        ->default(ScenarioStatusEnum::PENDING->value)
                        ->readonly(),
                    Forms\Components\Repeater::make('messages')->schema([
                        Forms\Components\Select::make('message_type')
                            ->required()
                            ->options(MessageTypeEnum::class)
                            ->reactive(),
                        Forms\Components\Select::make('message_id')
                            ->label('Select Message')
                            ->reactive()
                            ->required()
                            ->disabled(fn (Get $get) => empty($get('message_type')))
                            ->options(
                                function (Get $get) {
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
                                })
                            ->formatStateUsing(function ($record) {
                                if ($record) {
                                    return $record->messageDelivery?->message_id;
                                }
                            })
                            ->afterStateUpdated(
                                function (callable $set, ?string $state, Get $get) {
                                    $message = null;
                                    if (! is_null($state)) {
                                        switch ($get('message_type')) {
                                            case MessageTypeEnum::MESSAGE->value:
                                                $message = Message::find($state);
                                                break;
                                            case MessageTypeEnum::RICH_MESSAGE->value:
                                                $message = RichMessage::find($state);
                                                break;
                                            case MessageTypeEnum::RICH_VIDEO->value:
                                                $message = RichVideo::find($state);
                                                break;
                                            case MessageTypeEnum::RICH_CARD->value:
                                                $message = RichCard::find($state);
                                                break;
                                        }
                                        $file = $message->message != null ? null : $message->getFirstMedia('messages');
                                    }
                                    $set('message_name', $message->name ?? '');
                                    $set('messaging_type', $message->type ?? '');
                                    $set('message', $message->message ?? '');
                                    $set('file', $file ?? '');

                                    $set('rich_message_title', $message->title ?? '');
                                    $set('rich_message_created_at', $message->created_at ?? '');

                                }
                            ),
                        Forms\Components\DateTimePicker::make('delivery_date')
                            ->rules([new DateTimeRules(30)])
                            ->seconds(false)
                            ->native(false)
                            ->afterOrEqual(Carbon::now())
                            ->required(),
                        self::getMessagePreview()
                            ->visible(fn (\Filament\Forms\Get $get) => ($get('message_type') === MessageTypeEnum::MESSAGE->value && ! empty($get('message_id')))),
                        self::getRichMessagePreview()
                            ->visible(fn (\Filament\Forms\Get $get) => ($get('message_type') === MessageTypeEnum::RICH_MESSAGE->value && ! empty($get('message_id')))),
                    ])->formatStateUsing(function ($record) {
                        if (! $record) {
                            $array[] = [
                                'id' => null,
                                'message_id' => null,
                                'delivery_date' => null,
                            ];

                            return $array;
                        }
                        $array = [];
                        foreach ($record->messageDeliveries as $message) {
                            $file = $message->message->getFirstMedia('messages');
                            switch (true) {
                                case $message->message instanceof Message:
                                    $messageType = MessageTypeEnum::MESSAGE->value;
                                    break;
                                case $message->message instanceof RichMessage:
                                    $messageType = MessageTypeEnum::RICH_MESSAGE->value;
                                    break;
                                case $message->message instanceof RichVideo:
                                    $messageType = MessageTypeEnum::RICH_VIDEO->value;
                                    break;
                                case $message->message instanceof RichCard:
                                    $messageType = MessageTypeEnum::RICH_CARD->value;
                                    break;
                            }
                            $array[] = [
                                'id' => $message->id,
                                'message_id' => $message->message_id,
                                'delivery_date' => new DateTime($message->delivery_date),
                                'message_name' => $message->message->name,
                                'message_type' => $messageType,
                                'messaging_type' => $message->message->type,
                                'message' => $message->message->message,
                                'file' => $file ?? '',
                                'rich_message_title' => $message->message->title,
                                'rich_message_created_at' => $message->message->created_at,

                            ];
                        }

                        return $array;
                    })
                        ->orderColumn('delivery_date')
                        ->reorderable(false)
                        ->defaultItems(1)
                        ->columns(3)
                        ->columnSpanFull()
                        ->required()
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            $maxLength = 20;
                            $message = $state['message'] ?? null;

                            if ($message !== null && strlen($message) > $maxLength) {
                                $truncatedMessage = substr($message, 0, $maxLength).'...';
                            } else {
                                $truncatedMessage = $message;
                            }

                            return $truncatedMessage ?? null;
                        }),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function (ScenarioStatusEnum $state): string {
                        return ucfirst(str_replace('_', ' ', $state->value));
                    })
                    ->color(function ($state) {
                        $newState = str_replace(' ', '_', strtolower($state->value));

                        return match ($newState) {
                            ScenarioStatusEnum::COMPLETED->value => 'success',
                            ScenarioStatusEnum::ONGOING->value => 'info',
                            ScenarioStatusEnum::PENDING->value => 'warning',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('messageDeliveries')
                    ->formatStateUsing(function ($record) {
                        $delivery_date = $record->messageDeliveries->sortBy('delivery_date')->first()->delivery_date;

                        return $delivery_date->format('F j Y g:iA');
                    })
                    ->label(trans('Delivery Start')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                SelectFilter::make('status')
                    ->options(collect(ScenarioStatusEnum::cases())
                        ->mapWithKeys(fn (ScenarioStatusEnum $target) => [$target->value => ucfirst($target->value)])
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('activities')->url(fn ($record) => ScenarioDeliveryResource::getUrl('activities', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->using(function (Collection $records) {
                        try {
                            foreach ($records as $record) {
                                app(DeleteScenarioDeliveryAction::class)->execute($record);
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
            'index' => Pages\ListScenarioDeliveries::route('/'),
            'create' => Pages\CreateScenarioDelivery::route('/create'),
            'edit' => Pages\EditScenarioDelivery::route('/{record}/edit'),
            'activities' => Pages\ListScenarioDeliveryActivities::route('/{record}/activities'),
        ];
    }

    public static function getMessagePreview(): Forms\Components\Component
    {
        return Forms\Components\Fieldset::make('Message Preview')->schema([
            Forms\Components\TextInput::make('message_name')
                ->label('Name')
                ->disabled(),
            Forms\Components\TextInput::make('messaging_type')
                ->label('Type')
                ->disabled(),
            Forms\Components\Textarea::make('message')
                ->hidden(fn (\Filament\Forms\Get $get) => $get('messaging_type') != MessagingTypeEnum::TEXT)
                ->label('Message')
                ->columnSpanFull()
                ->disabled(),
            Forms\Components\Placeholder::make('file')
                ->hidden(fn (\Filament\Forms\Get $get) => $get('messaging_type') == MessagingTypeEnum::TEXT || $get('messaging_type') == null)
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
                                                <source src="'.$state->getUrl().'" type="video/mp4">
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
        return Forms\Components\Fieldset::make('Rich Message Preview')->schema([
            Forms\Components\Group::make()->schema([

                Forms\Components\TextInput::make('rich_message_title')
                    ->label('Title')
                    ->disabled(),
                Forms\Components\TextInput::make('rich_message_created_at')
                    ->label('Date Created')
                    ->disabled(),
            ])
                ->columnSpan(3),
            Forms\Components\Group::make()->schema([
                Forms\Components\Placeholder::make('')->content(function ($get) {
                    $richMessage = RichMessage::find($get('message_id'));

                    return new HtmlString(
                        '<div style="position: relative">
                        <img src="'.$richMessage->getFirstMediaUrl('messages').'" style="max-height: 200px; border-radius: 8px; position: relative; top: 0px; right: 0px; "/>
    
                        </div>'
                        // <img src="'.asset('layout/richmessage/layout-'. $richMessage->layout_rich_message_id .'.svg').'" style="max-height: 400px; border-radius: 8px; position: absolute; top: 0px; right: 0px; background-color: rgba(0, 0, 0, 0.4)"/>
                    );
                }),
            ])->columnSpan(1),
        ])->columns(4);
    }
}
