<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Actions\Broadcast\CreateBroadcastAction;
use App\Actions\MessageDelivery\CreateMessageDeliveryAction;
use App\Console\Commands\BroadcastCommand;
use App\DataTransferObjects\BroadcastData;
use App\DataTransferObjects\MessageDeliveryData;
use App\Enums\FlagEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\RepeatEnum;
use App\Enums\UsedForEnum;
use App\Filament\Resources\BroadcastResource;
use App\Jobs\BroadcastingJob;
use App\Models\Broadcast;
use App\Models\Friend;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * 新規配信作成画面。
 *
 * 旧来の単一フォーム (BroadcastResource::form()) を 4 ステップのウィザードに再構成。
 * 既存のビジネスロジック (handleRecordCreation) は保持し、UI のみを改善。
 *
 * ステップ:
 *   1. 配信対象 (send_to) — 対象者数をライブ表示
 *   2. コンテンツ — message_type / message_id の選択 + プレビュー
 *   3. スケジュール — 今すぐ / 予約 / 繰り返し
 *   4. 確認 — サマリー表示
 */
class CreateBroadcast extends CreateRecord
{
    protected static string $resource = BroadcastResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('基本情報・配信対象')
                    ->description('配信名と送信するセグメントを選択します。対象者数はライブで再計算されます。')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('配信名')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('send_to')
                            ->label('配信対象')
                            ->required()
                            ->reactive()
                            ->options(collect(FlagEnum::cases())
                                ->mapWithKeys(fn (FlagEnum $t) => [$t->value => $t->getLabel()])
                                ->prepend('すべての友だち', 'all')
                                ->toArray()),
                        Forms\Components\Placeholder::make('recipient_count_live')
                            ->label('対象者数 (ライブカウント)')
                            ->content(function (Get $get) {
                                $sendTo = $get('send_to');
                                if (empty($sendTo)) {
                                    return new HtmlString('<span class="text-gray-500">配信対象を選択してください。</span>');
                                }
                                $count = Friend::query()
                                    ->when($sendTo !== 'all', fn ($q) => $q->where('mark', $sendTo))
                                    ->count();

                                return new HtmlString(
                                    '<span style="font-size:1.5rem; font-weight:700; color:#21D59B">'
                                    .number_format($count)
                                    .' 人</span><span class="text-gray-500 ml-2">に配信されます</span>'
                                );
                            }),
                    ])->columns(1),

                Forms\Components\Wizard\Step::make('コンテンツ選択')
                    ->description('配信するメッセージを選びます。')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Select::make('message_type')
                            ->label('メッセージ種別')
                            ->options(MessageTypeEnum::class)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('message_id', null)),
                        Forms\Components\Select::make('message_id')
                            ->label('メッセージを選択')
                            ->required()
                            ->reactive()
                            ->disabled(fn (Get $get) => empty($get('message_type')))
                            ->options(function (Get $get) {
                                return match ($get('message_type')) {
                                    MessageTypeEnum::MESSAGE->value => Message::whereUsedFor(UsedForEnum::AUTO_RESPONSE)
                                        ->orderBy('updated_at', 'desc')->pluck('name', 'id'),
                                    MessageTypeEnum::RICH_MESSAGE->value => RichMessage::orderBy('updated_at', 'desc')->pluck('title', 'id'),
                                    MessageTypeEnum::RICH_VIDEO->value => RichVideo::orderBy('updated_at', 'desc')->pluck('name', 'id'),
                                    MessageTypeEnum::RICH_CARD->value => RichCard::orderBy('updated_at', 'desc')->pluck('name', 'id'),
                                    default => [],
                                };
                            }),
                        Forms\Components\Placeholder::make('message_summary')
                            ->label('プレビュー')
                            ->content(function (Get $get) {
                                $id = $get('message_id');
                                if (empty($id)) {
                                    return new HtmlString('<p class="text-sm text-gray-500">メッセージを選択するとプレビューが表示されます。</p>');
                                }
                                $model = match ($get('message_type')) {
                                    MessageTypeEnum::MESSAGE->value => Message::find($id),
                                    MessageTypeEnum::RICH_MESSAGE->value => RichMessage::find($id),
                                    MessageTypeEnum::RICH_VIDEO->value => RichVideo::find($id),
                                    MessageTypeEnum::RICH_CARD->value => RichCard::find($id),
                                    default => null,
                                };
                                if (! $model) {
                                    return new HtmlString('<p class="text-sm text-gray-500">メッセージが見つかりません。</p>');
                                }

                                $title = $model->title ?? $model->name ?? '(無題)';
                                $bodyPreview = $model->message ?? $model->description ?? '';

                                return new HtmlString(
                                    '<div style="padding:.75rem; border:1px solid #e5e7eb; border-radius:8px">'
                                    .'<div style="font-weight:600">'.e($title).'</div>'
                                    .($bodyPreview ? '<div style="margin-top:.25rem; font-size:.875rem; color:#4b5563">'.e($bodyPreview).'</div>' : '')
                                    .'</div>'
                                );
                            }),
                    ])->columns(1),

                Forms\Components\Wizard\Step::make('スケジュール')
                    ->description('今すぐ送る、または時刻指定・繰り返し配信を設定します。')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Radio::make('is_send_now')
                            ->label('配信タイミング')
                            ->boolean('今すぐ配信', '時刻を指定 / 繰り返し')
                            ->inline()
                            ->reactive()
                            ->default(false)
                            ->required()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $set('start_date', now()->toDateTimeString());
                                }
                            }),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('配信開始日時')
                            ->required(fn (Get $get) => ! $get('is_send_now'))
                            ->disabled(fn (Get $get) => (bool) $get('is_send_now'))
                            ->default(now()->addMinutes(5)->startOfMinute())
                            ->seconds(false)
                            ->displayFormat('Y/m/d H:i'),
                        Forms\Components\Select::make('repeat')
                            ->label('繰り返し')
                            ->options(collect(RepeatEnum::cases())
                                ->mapWithKeys(fn (RepeatEnum $t) => [$t->value => ucfirst(str_replace('_', ' ', $t->value))]))
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('every')
                            ->label('間隔')
                            ->disabled(fn (Get $get) => empty($get('repeat')) || $get('repeat') === RepeatEnum::ONCE->value)
                            ->required(fn (Get $get) => ! empty($get('repeat')) && $get('repeat') !== RepeatEnum::ONCE->value)
                            ->options(function (Get $get) {
                                return match ($get('repeat')) {
                                    RepeatEnum::MINUTES->value => [15 => '15 分', 30 => '30 分', 45 => '45 分'],
                                    RepeatEnum::HOUR->value => collect(range(1, 24))->mapWithKeys(fn ($n) => [$n => "{$n} 時間"])->all(),
                                    RepeatEnum::DAY->value => collect(range(1, 31))->mapWithKeys(fn ($n) => [$n => "{$n} 日"])->all(),
                                    RepeatEnum::WEEK->value => [1 => '1 週', 2 => '2 週', 3 => '3 週'],
                                    RepeatEnum::MONTH->value => collect(range(1, 12))->mapWithKeys(fn ($n) => [$n => "{$n} ヶ月"])->all(),
                                    default => [],
                                };
                            }),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('確認')
                    ->description('設定内容を確認して配信してください。')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('summary')
                            ->label('配信内容サマリー')
                            ->content(function (Get $get) {
                                $count = Friend::query()
                                    ->when(($get('send_to') ?? null) && $get('send_to') !== 'all',
                                        fn ($q) => $q->where('mark', $get('send_to')))
                                    ->count();

                                $rows = [
                                    '配信名' => $get('name') ?? '—',
                                    '配信対象' => $get('send_to') === 'all' ? 'すべての友だち' : (string) $get('send_to'),
                                    '対象者数' => number_format($count).' 人',
                                    'メッセージ種別' => $get('message_type') ?? '—',
                                    '配信タイミング' => $get('is_send_now') ? '今すぐ配信' : (string) $get('start_date'),
                                    '繰り返し' => $get('repeat') ?? 'once',
                                ];
                                $html = '<table style="width:100%; font-size:.875rem;">';
                                foreach ($rows as $k => $v) {
                                    $html .= '<tr><td style="padding:.25rem .75rem; color:#6b7280">'.e($k).'</td>'
                                          .'<td style="padding:.25rem .75rem; font-weight:600">'.e($v).'</td></tr>';
                                }
                                $html .= '</table>';

                                return new HtmlString($html);
                            }),
                    ]),
            ])
            ->columnSpanFull()
            ->submitAction(new HtmlString(
                '<button type="submit" class="fi-btn fi-btn-color-primary fi-btn-size-md inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2 bg-primary-600 text-white font-medium">配信を作成</button>'
            )),
        ]);
    }

    public function handleRecordCreation(array $data): Model
    {
        $data['is_active'] = true;
        if ($data['is_send_now']) {
            $data['start_date'] = now()->toDateTimeString();

            if ($data['repeat'] === RepeatEnum::ONCE->value) {
                $data['is_active'] = false;
            }
        }

        if ($data['is_active'] === false) {
            $data['last_date'] = now()->toDateTimeString();
        }

        $broadCastData = BroadcastData::fromArray($data);

        $broadcast = DB::transaction(function () use ($data, $broadCastData) {
            $broadcast = app(CreateBroadcastAction::class)->execute($broadCastData);

            $messageType = match ($data['message_type']) {
                MessageTypeEnum::MESSAGE->value => Message::class,
                MessageTypeEnum::RICH_MESSAGE->value => RichMessage::class,
                MessageTypeEnum::RICH_VIDEO->value => RichVideo::class,
                MessageTypeEnum::RICH_CARD->value => RichCard::class,
            };

            app(CreateMessageDeliveryAction::class)->execute(MessageDeliveryData::fromArray([
                'message_id' => $data['message_id'],
                'message_type' => $messageType,
                'delivery_id' => $broadcast->id,
                'delivery_type' => Broadcast::class,
                'delivery_date' => null,
            ]));

            return $broadcast;
        });

        if ($broadCastData->is_send_now) {
            BroadcastingJob::dispatch($broadcast->messageDelivery->message, $broadcast->send_to);
            app(BroadcastCommand::class)->updateNextDate($broadcast);

            if ($broadCastData->repeat === RepeatEnum::ONCE) {
                $broadcast->update(['is_active' => false]);
            }
        }

        return $broadcast;
    }
}
