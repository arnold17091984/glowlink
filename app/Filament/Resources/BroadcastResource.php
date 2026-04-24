<?php

namespace App\Filament\Resources;

use App\Actions\Broadcast\DeleteBroadcastAction;
use App\Enums\FlagEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\MessagingTypeEnum;
use App\Enums\RepeatEnum;
use App\Enums\UsedForEnum;
use App\Filament\Resources\BroadcastResource\Pages;
use App\Models\Broadcast;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use LogicException;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationGroup = 'メッセージ';

    protected static ?string $navigationLabel = 'ブロードキャスト';

    protected static ?string  = 'ブロードキャスト';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Select::make('message_type')
                        ->afterStateUpdated(function ($set) {
                            $set('message_id', null);
                        })
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
                        ->reactive(),
                    Forms\Components\Select::make('message_id')
                        ->label('Select Message')
                        ->disabled(fn ($get) => empty($get('message_type')))
                        ->reactive()
                        ->required()
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
                            function (callable $set, ?string $state, Get $get) {
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
                    Forms\Components\Select::make('send_to')
                        ->label('Send To')
                        ->options(collect(FlagEnum::cases())
                            ->mapWithKeys(fn (FlagEnum $target) => ['all' => 'All', $target->value => ucfirst(str_replace('_', ' ', $target->value))]))
                        ->required(),
                    Forms\Components\Radio::make('is_send_now')
                        ->boolean()
                        ->label('Send now?')
                        ->reactive()
                        ->default(false)
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $set('start_date', null);

                                return;
                            }

                            $set('start_date', now()->toDateTimeString());

                        })
                        ->disabled(fn (?Broadcast $record) => $record?->start_date->lessThan(now()->toDateTimeString()))
                        ->inline()
                        ->dehydrated()
                        ->inlineLabel(false),
                    Forms\Components\DateTimePicker::make('start_date')
                        ->default(now()->addMinute()->startOfMinute())
                        ->displayFormat('F d, Y h:i A')
                        ->minDate(function ($livewire) {
                            if ($livewire instanceof CreateRecord) {
                                return now()->addMinute()->startOfMinute();
                            }
                        })
                        ->seconds(false)
                        // ->minutesStep(15)
                        ->disabled(fn ($record, $state, $get) => ! is_null($record) && $state <= now() ||
                        $get('is_send_now') ||
                        $record?->start_date->lessThan(now()->toDateTimeString()))
                        ->dehydrated()
                        ->required(fn ($get) => ! $get('is_send_now')),
                    Forms\Components\Select::make('repeat')
                        ->reactive()
                        ->label('Repeat')
                        ->disabled(fn ($record) => ! is_null($record?->start_date) && $record->start_date->lessThan(now()->toDateTimeString()))
                        ->dehydrated()
                        ->options(collect(RepeatEnum::cases())
                            ->mapWithKeys(fn (RepeatEnum $target) => [$target->value => ucfirst(str_replace('_', ' ', $target->value))]))
                        ->required(),
                    Forms\Components\Select::make('every')
                        ->disabled(function (\Filament\Forms\Get $get, ?Broadcast $record) {
                            if ($record && ! is_null($record->start_date) && $record->start_date->lessThan(now()->toDateTimeString())) {
                                return true;
                            }
                            if (! $get('repeat')) {
                                return true;
                            }
                            if ($get('repeat') === RepeatEnum::ONCE->value) {
                                return true;
                            }

                            return false;
                        })
                        ->dehydrated()
                        ->required(function (\Filament\Forms\Get $get) {
                            return $get('repeat') != RepeatEnum::ONCE->value;
                        })
                        ->label('Every')
                        ->options(function (\Filament\Forms\Get $get) {
                            $day = [];
                            $hours = [];
                            $minutes = [
                                15 => '15 minutes',
                                30 => '30 minutes',
                                45 => '45 minutes',
                            ];
                            $week = [
                                1 => '1 week',
                                2 => '2 week',
                                3 => '3 week',
                            ];
                            $month = [];
                            foreach (range(1, 31) as $number) {
                                $day[$number] = $number.' Day/s';
                            }
                            foreach (range(1, 31) as $number) {
                                $day[$number] = $number.' Day/s';
                            }
                            foreach (range(1, 24) as $number) {
                                $hours[$number] = $number.' Hour/s';
                            }
                            foreach (range(1, 12) as $number) {
                                $month[$number] = $number.' Month/s';
                            }
                            if ($get('repeat') === RepeatEnum::DAY->value) {
                                return $day;
                            }
                            if ($get('repeat') === RepeatEnum::HOUR->value) {
                                return $hours;
                            }
                            if ($get('repeat') === RepeatEnum::MINUTES->value) {
                                return $minutes;
                            }
                            if ($get('repeat') === RepeatEnum::WEEK->value) {
                                return $week;
                            }
                            if ($get('repeat') === RepeatEnum::MONTH->value) {
                                return $month;
                            }

                            return [];
                        }),
                ])->columns(2)
                    ->columnspan(2),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Options')->schema([
                        Forms\Components\DateTimePicker::make('next_date')
                            ->label('Next Delivery Date: ')
                            ->native(false)
                            ->displayFormat('F d, Y h:i A')
                            ->disabled()
                            ->visible(fn (?Broadcast $record, $livewire) => ! $livewire instanceof CreateRecord &&
                             ! is_null($record->every) &&
                              is_null($record->last_date) &&
                              ! is_null($record->next_date)),
                        Forms\Components\DateTimePicker::make('last_date')
                            ->label('Last Delivery Date: ')
                            ->native(false)
                            ->displayFormat('F d, Y h:i A')
                            ->disabled()
                            ->visible(fn (?Broadcast $record, $livewire) => ! $livewire instanceof CreateRecord && ! is_null($record->last_date)),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(fn ($record) => ! $record->is_active)
                            ->default(true)
                            ->required(),
                    ])->hidden(fn ($livewire) => $livewire instanceof CreateRecord),

                    self::getPreview(),
                ])
                    ->columnspan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['messageDelivery.message']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('配信名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('send_to')
                    ->label('配信対象')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('配信予定日')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_date')
                    ->label('次回配信日')
                    ->dateTime('Y/m/d H:i')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('repeat')
                    ->label('繰り返し')
                    ->badge(),
                Tables\Columns\TextColumn::make('every')
                    ->label('間隔')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日時')
                    ->dateTime('Y/m/d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日時')
                    ->dateTime('Y/m/d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('有効'),
                Tables\Filters\SelectFilter::make('repeat')
                    ->label('繰り返し')
                    ->options(RepeatEnum::class),
                Tables\Filters\SelectFilter::make('send_to')
                    ->label('配信対象')
                    ->options(fn () => collect(FlagEnum::cases())
                        ->mapWithKeys(fn (FlagEnum $t) => [$t->value => ucfirst(str_replace('_', ' ', $t->value))])
                        ->prepend('All', 'all')
                        ->toArray()),
                Tables\Filters\Filter::make('scheduled')
                    ->label('予約済み')
                    ->query(fn ($q) => $q->whereNotNull('start_date')->where('start_date', '>', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activities')->url(fn ($record) => BroadcastResource::getUrl('activities', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->using(function (Collection $records) {
                        try {
                            foreach ($records as $record) {
                                app(DeleteBroadcastAction::class)->execute($record);
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
            'index' => Pages\ListBroadcasts::route('/'),
            'create' => Pages\CreateBroadcast::route('/create'),
            'edit' => Pages\EditBroadcast::route('/{record}/edit'),
            'view' => Pages\ViewBroadcast::route('/{record}'),
            'activities' => Pages\ListBroadcastActivities::route('/{record}/activities'),
        ];
    }

    public static function getPreview(): Forms\Components\Component
    {
        return Forms\Components\Group::make()->schema([
            self::getMessagePreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::MESSAGE->value),

            self::getRichMessagePreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::RICH_MESSAGE->value),

            self::getRichCardPreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::RICH_CARD->value),

            self::getRichVideoPreview()
                ->hidden(fn ($get) => $get('message_type') !== MessageTypeEnum::RICH_VIDEO->value),
        ]);
    }

    public static function getRichCardPreview(): Forms\Components\Component
    {
        return Forms\Components\Section::make('リッチカード プレビュー')->schema([
            Forms\Components\Placeholder::make('rich_card_preview')
                ->hiddenLabel()
                ->content(function ($get) {
                    $id = $get('message_id');
                    if (empty($id)) {
                        return new HtmlString('<p class="text-sm text-gray-500">メッセージを選択するとプレビューが表示されます。</p>');
                    }
                    $richCard = RichCard::with('media')->find($id);
                    if (! $richCard) {
                        return new HtmlString('<p class="text-sm text-gray-500">カードが見つかりません。</p>');
                    }
                    $cards = $richCard->getMedia('messages')->map(function ($media) {
                        return '<div style="flex:0 0 220px; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.1)">
                            <img src="'.$media->getUrl().'" style="width:100%; height:140px; object-fit:cover"/>
                        </div>';
                    })->implode('');

                    return new HtmlString(
                        '<div style="display:flex; gap:12px; overflow-x:auto; padding:8px 0">'.$cards.'</div>'
                    );
                }),
        ])->columnSpan(1);
    }

    public static function getRichVideoPreview(): Forms\Components\Component
    {
        return Forms\Components\Section::make('リッチビデオ プレビュー')->schema([
            Forms\Components\Placeholder::make('rich_video_preview')
                ->hiddenLabel()
                ->content(function ($get) {
                    $id = $get('message_id');
                    if (empty($id)) {
                        return new HtmlString('<p class="text-sm text-gray-500">メッセージを選択するとプレビューが表示されます。</p>');
                    }
                    $richVideo = RichVideo::find($id);
                    if (! $richVideo) {
                        return new HtmlString('<p class="text-sm text-gray-500">ビデオが見つかりません。</p>');
                    }
                    $url = $richVideo->getFirstMediaUrl('messages');
                    if (! $url) {
                        return new HtmlString('<p class="text-sm text-gray-500">動画が未アップロードです。</p>');
                    }

                    return new HtmlString(
                        '<div class="video-player">
                            <video controls style="max-height:400px; border-radius:8px; width:100%">
                                <source src="'.$url.'" type="video/mp4">
                                お使いのブラウザは動画再生に対応していません。
                            </video>
                        </div>'
                    );
                }),
        ])->columnSpan(1);
    }

    public static function getMessagePreview(): Forms\Components\Component
    {
        return Forms\Components\Section::make('Message Preview')->schema([
            Forms\Components\TextInput::make('message_name')
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->name;
                    }
                })
                ->label('Name')
                ->disabled(),
            Forms\Components\TextInput::make('messaging_type')
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->type;
                    }
                })
                ->label('Type')
                ->disabled(),
            Forms\Components\Textarea::make('message')
                ->hidden(fn (\Filament\Forms\Get $get) => $get('message_type') != MessagingTypeEnum::TEXT)
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->message;
                    }
                })
                ->label('Message')
                ->disabled(),
            Forms\Components\Placeholder::make('file')
                ->hidden(fn (\Filament\Forms\Get $get) => $get('message_type') == MessagingTypeEnum::TEXT || $get('message_type') == null)
                ->formatStateUsing(function ($record) {
                    if ($record) {
                        return $record->messageDelivery->message->getFirstMedia('messages');
                    }
                })
                ->content(
                    function ($state, \Filament\Forms\Get $get) {
                        if (! is_null($state)) {
                            switch ($get('message_type')) {
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
        ])->columnSpan(1);
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
}
