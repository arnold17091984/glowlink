<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LineChannelResource\Pages;
use App\Models\LineChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LineChannelResource extends Resource
{
    protected static ?string $model = LineChannel::class;

    protected static ?string $navigationGroup = 'チャネル接続';

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $modelLabel = 'LINE 公式アカウント';

    protected static ?string $pluralModelLabel = 'LINE 公式アカウント';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基本情報')
                ->description('公式アカウント名と接続識別子を設定します。')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('公式アカウント名')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('例: betrnk tours 公式')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, ?string $operation) {
                            if ($operation === 'create' && $state) {
                                $set('slug', Str::slug($state, '-'));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (Webhook URL 識別子)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(80)
                        ->alphaDash()
                        ->helperText('Webhook URL の末尾として使われます。英数字とハイフンのみ。')
                        ->prefix('/messages/'),
                    Forms\Components\TextInput::make('basic_id')
                        ->label('ベーシック ID')
                        ->maxLength(40)
                        ->placeholder('@xxxxxxx'),
                ])->columns(3),

            Forms\Components\Section::make('LINE Messaging API 資格情報')
                ->description('LINE Developers Console > プロバイダー > チャネル > Messaging API 設定 から取得します。')
                ->schema([
                    Forms\Components\TextInput::make('channel_id')
                        ->label('Channel ID')
                        ->required()
                        ->numeric()
                        ->placeholder('1234567890'),
                    Forms\Components\TextInput::make('channel_secret')
                        ->label('Channel Secret')
                        ->required()
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->placeholder('32 文字の hex')
                        ->helperText('暗号化して DB に保存されます。'),
                    Forms\Components\Textarea::make('channel_access_token')
                        ->label('Channel Access Token (Long-lived)')
                        ->required()
                        ->rows(3)
                        ->placeholder('eyJhbGciOiJIUzI1NiJ9...')
                        ->helperText('暗号化して DB に保存されます。')
                        ->columnSpanFull()
                        ->extraInputAttributes(['style' => 'font-family: "IBM Plex Mono", monospace; font-size: 0.85rem;']),
                ])->columns(2),

            Forms\Components\Section::make('LIFF 連携（任意）')
                ->description('クーポンウォレット等で使う LIFF ID を紐付けます。')
                ->schema([
                    Forms\Components\TextInput::make('liff_id')
                        ->label('LIFF ID')
                        ->maxLength(100)
                        ->placeholder('1234567890-AbCdEfGh'),
                ])
                ->collapsed(fn ($record) => ! $record?->liff_id),

            Forms\Components\Section::make('動作設定')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('有効')
                        ->default(true)
                        ->helperText('無効化すると Webhook を受け付けません。'),
                    Forms\Components\Toggle::make('is_default')
                        ->label('デフォルトチャネル')
                        ->helperText('旧 /messages ルートや CLI 送信のデフォルト送信先。')
                        ->default(false),
                    Forms\Components\Textarea::make('notes')
                        ->label('メモ')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('公式アカウント')
                    ->searchable()
                    ->sortable()
                    ->description(fn (LineChannel $record) => $record->basic_id ?: null),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel_id')
                    ->label('Channel ID')
                    ->fontFamily('mono')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('DEFAULT')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('ACTIVE')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_connected_at')
                    ->label('最終接続')
                    ->dateTime('Y/m/d H:i')
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('登録日')
                    ->dateTime('Y/m/d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('有効'),
                Tables\Filters\TernaryFilter::make('is_default')->label('デフォルト'),
            ])
            ->actions([
                Tables\Actions\Action::make('copyWebhook')
                    ->label('Webhook URL')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (LineChannel $record, $livewire) {
                        $livewire->js('navigator.clipboard.writeText('.json_encode($record->webhookUrl()).')');
                        Notification::make()
                            ->title('コピーしました')
                            ->body($record->webhookUrl())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('testConnection')
                    ->label('接続テスト')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->action(function (LineChannel $record) {
                        try {
                            $response = Http::withToken($record->channel_access_token)
                                ->timeout(10)
                                ->get('https://api.line.me/v2/bot/info');

                            if ($response->successful()) {
                                $record->update(['last_connected_at' => now()]);
                                $info = $response->json();
                                Notification::make()
                                    ->title('接続成功')
                                    ->body('Display name: '.($info['displayName'] ?? '(unknown)').' / Premium: '.($info['premiumId'] ?? '-'))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('接続失敗')
                                    ->body('HTTP '.$response->status().' — '.$response->body())
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('接続エラー')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc')
            ->emptyStateHeading('LINE 公式アカウント未登録')
            ->emptyStateDescription('右上の「新規登録」から最初のチャネルを接続してください。')
            ->emptyStateIcon('heroicon-o-link-slash');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLineChannels::route('/'),
            'create' => Pages\CreateLineChannel::route('/create'),
            'edit' => Pages\EditLineChannel::route('/{record}/edit'),
        ];
    }
}
