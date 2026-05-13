<?php

namespace App\Filament\Resources;

use App\Enums\RichMenuActionEnum;
use App\Filament\Resources\RichMenuResource\Pages;
use App\Filament\Resources\RichMenuResource\RelationManagers\ChildrenRelationManager;
use App\Forms\Components\RichMenuLayout;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\AutoResponse;
use App\Models\RichMenu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use LogicException;

class RichMenuResource extends Resource
{
    protected static ?string $model = RichMenu::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static string $parentResource = RichMenuSetResource::class;

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->title;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('chatbar_text')->maxLength(12)->reactive(),
                        Forms\Components\Toggle::make('selected')->label(trans('Initial state of menu')),
                        Forms\Components\Fieldset::make('Layout')->schema([
                            Forms\Components\Repeater::make('actions')
                                // 旧実装は画像未アップロード時に無効化していたが、
                                // ユーザーが「アクションを先に設定したい」「画像とアクションを並行で詰めたい」
                                // ケースに対応できなかった。画像は保存時 required で検証されるので
                                // フォーム上の常時アクセスは許可してしまって OK。
                                ->disabled(false)
                                ->schema([
                                    Forms\Components\Select::make('action')
                                        ->required()
                                        ->reactive()
                                        ->options(RichMenuActionEnum::class),
                                    Forms\Components\Select::make('auto_response_id')
                                        ->label('Auto Response')
                                        ->reactive()
                                        ->columnSpanFull()
                                        ->required()
                                        ->visible(fn (Get $get) => $get('action') === RichMenuActionEnum::AUTO_RESPONSE->value)
                                        ->options(function () {
                                            return AutoResponse::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                        }),
                                    Forms\Components\Select::make('children_id')
                                        ->label('Sub Rich Menu')
                                        ->reactive()
                                        ->columnSpanFull()
                                        ->required()
                                        ->visible(fn (Get $get) => $get('action') === RichMenuActionEnum::SUB_MENU->value)
                                        ->options(function ($record) {
                                            if ($record) {
                                                return RichMenu::orderBy('updated_at', 'desc')?->whereParentId($record->id)->pluck('name', 'id');
                                            }
                                        }),
                                    Forms\Components\TextInput::make('link')
                                        ->label('リンク URL')
                                        ->required()
                                        ->placeholder('https://example.com')
                                        ->helperText('http:// または https:// から始まる URL を入力してください。')
                                        ->prefix('🔗')
                                        ->regex('#^(https?://|line://).+#i')
                                        ->validationMessages([
                                            'regex' => 'http:// または https:// で始まる URL を入力してください',
                                        ])
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::LINK->value),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('電話番号')
                                        ->required()
                                        ->placeholder('03-1234-5678 / 0312345678 / +81312345678')
                                        ->helperText('数字、ハイフン、+ のみ。タップで LINE が発信ダイヤラーを開きます。')
                                        ->prefix('📞')
                                        ->tel()
                                        ->regex('#^[\+\d][\d\-\s\(\)]{7,}$#')
                                        ->validationMessages([
                                            'regex' => '電話番号は数字 / ハイフン / + で構成してください',
                                        ])
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::PHONE->value),
                                    Forms\Components\TextInput::make('mail')
                                        ->label('メールアドレス')
                                        ->required()
                                        ->placeholder('contact@example.com')
                                        ->helperText('タップで LINE がメールアプリを開きます。')
                                        ->prefix('✉️')
                                        ->email()
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::MAIL->value),
                                    Forms\Components\Textarea::make('text')
                                        ->label('送信するメッセージ')
                                        ->required()
                                        ->helperText('タップしたユーザーがあなたに送るメッセージとして表示されます。')
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::MESSAGE->value),
                                    Forms\Components\Placeholder::make('share_oa_helper')
                                        ->label('友だちに公式アカウントを紹介')
                                        ->content('このセルをタップすると、ユーザーの LINE 友だち選択画面が開き、この公式アカウントを紹介できます。設定不要で動きます（紐づけているチャネルのベーシック ID を自動利用）。')
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::SHARE_OA->value),
                                    Forms\Components\Textarea::make('share_text')
                                        ->label('共有メッセージ本文')
                                        ->required()
                                        ->placeholder('ベットランクツアーズで賢く旅しよう！特別プランをチェック → https://glowlink.ink')
                                        ->helperText('タップしたユーザーが LINE 友だちに送るメッセージ。URL も含められます。')
                                        ->maxLength(300)
                                        ->hidden(fn (Get $get) => $get('action') !== RichMenuActionEnum::SHARE_MESSAGE->value),
                                ])
                                ->itemLabel(function ($uuid, $component) {
                                    $keys = array_keys($component->getState());
                                    $index = array_search($uuid, $keys);
                                    $alphabet = range('A', 'Z');

                                    return $alphabet[$index];
                                })
                                ->hiddenLabel()
                                ->deletable(false)
                                ->addable(false)
                                // Drag-and-drop reorder so operators can move cells around the layout.
                                // Bound to cell index in GetActionsAction (areas[i] <- actions[i]),
                                // so reordering directly changes which cell each action occupies.
                                ->reorderable()
                                ->reorderableWithDragAndDrop()
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->columnSpan(2),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Layout')->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageEditor()
                            ->imageEditorViewportWidth('2500')
                            ->imageEditorViewportHeight('1686')
                            ->imageCropAspectRatio('2500:1686')
                            ->imageResizeTargetWidth('2500')
                            ->imageResizeTargetHeight('1686')
                            ->previewable(false)
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->disk(config('media-library.disk_name', 'public'))
                            ->collection('richmenus')
                            ->required()
                            ->reactive()
                            ->live()
                            ->hintIcon(
                                'heroicon-m-question-mark-circle',
                                'JPG/PNG 1MB 以下、画像サイズは 2500x1686 (フル) または 2500x843 (コンパクト) を推奨'
                            ),
                        RichMenuLayout::make('selected_layout')
                            ->default(1)
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\TextInput::make('tab_no')->label('Enter Tab Number')
                            ->default(function ($livewire) {

                                $numbers = $livewire->parent->richMenus->pluck('tab_no')->toArray();
                                if (! $numbers) {
                                    return 1;
                                }
                                $highestNumber = max($numbers);
                                $lowestMissingNumber = self::findMissingNumber($highestNumber, $numbers);

                                if (! is_null($lowestMissingNumber)) {
                                    return $lowestMissingNumber;
                                }

                                $highestTabNumber = (int) $livewire->parent->richMenus->max('tab_no');
                                $defaultTabNumber = $highestTabNumber ? $highestTabNumber + 1 : 1;

                                return $defaultTabNumber;
                            })->disabled(),
                    ]),
                    Forms\Components\Placeholder::make('')->content(function ($record, $state, $livewire) {
                        $layout_no = (int) $livewire->parent->layout_no;
                        $selected_layout = $state['selected_layout'];
                        $chatbar_text = $state['chatbar_text'] ?? 'Menu';
                        $tabNumber = $state['tab_no'] ?? 1;
                        $tabImage = '';
                        $layoutImage = '<img src="'.
                        asset('layout/richmenu/layout-'.$selected_layout.'.svg').
                        '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        if ($layout_no !== 1) {
                            $tabImage = '<img src="'.
                            asset('layout/tab/tab-'.$layout_no.'/'.self::determineTabLayout($tabNumber).'.svg').
                            '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        } else {
                            $layoutImage = '<img src="'.
                            asset('layout/richmenu/noTab/layout-'.$selected_layout.'.svg').
                            '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        }

                        $image = '';

                        if ($state['image'] && ! $record) {
                            $firstValue = reset($state['image']);
                            // S3 ハードコードは AWS 未設定時に SDK 検証エラーで 500 を出す。
                            // MEDIA_DISK (デフォルト public) を使い、cloud disk のときのみ
                            // temporaryUrl、それ以外は Storage::url を返す。
                            $diskName = config('media-library.disk_name', 'public');
                            $disk = Storage::disk($diskName);
                            $filePath = $disk->putFile('temp', $firstValue->getRealPath());
                            try {
                                $image = $disk->temporaryUrl($filePath, now()->addMinutes(15));
                            } catch (\Throwable $e) {
                                // local public 等は temporaryUrl 非対応 → 通常 URL
                                $image = $disk->url($filePath);
                            }
                        }

                        if ($record) {
                            $image = $record->getFirstMediaUrl('richmenus');
                        }

                        return new HtmlString(
                            ' <div style="position: relative; height: 100%;">
                            '.
                                ($state['image'] || $record ? '<img src="'.$image.'" style="position: absolute; top: 0px; right: 0px; z-index: 1;" draggable="false"/>' : '').
                                '
                            '.$tabImage.''.$layoutImage.'
                                <div style=" width: 100%; background: white; color: black; padding: 10px; display:flex; align-items: center; justify-content: center;">
                                <img src="'.
                                asset('layout/richmenu/keyboard.svg').
                                '" style="width: 30px; position: absolute; left: 20px"/>
                                    <div style="text-align: center;">'.
                                    ($chatbar_text ? $chatbar_text : 'Menu').
                                    '</div>
                                </div>
                            </div>'
                        );
                    }),
                ]),

            ])->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        return RichMenu::whereParentId(null);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
                    ->collection('richmenus')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('tab_no')->searchable(),
                Tables\Columns\IconColumn::make('selected')->boolean(),
                Tables\Columns\TextColumn::make('chatbar_text')->searchable(),
                Tables\Columns\TextColumn::make('width')->searchable(),
                Tables\Columns\TextColumn::make('height')->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('name')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(
                        fn (Pages\ListRichMenus $livewire, Model $record): string => static::$parentResource::getUrl('rich-menus.edit', [
                            'record' => $record,
                            'parent' => $livewire->parent,
                        ])
                    ),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->using(function (Collection $records) {
                        try {
                            foreach ($records as $richMenu) {
                                if ($richMenu->richMenuSet->is_active) {
                                    DeleteRichMenuLineJob::dispatch($richMenu);
                                }
                                $richMenu->delete();
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
            ChildrenRelationManager::class,
        ];
    }

    public static function determineTabLayout(string $tabNumber): string
    {
        $tabNumber = (int) $tabNumber;

        if ($tabNumber == 1) {
            return 'tab-1';
        } elseif ($tabNumber == 2) {
            return 'tab-2';
        } elseif ($tabNumber == 3) {
            return 'tab-3';
        } elseif ($tabNumber % 2 == 0) {
            return 'tab-4';
        } else {
            return 'tab-3';
        }
    }

    public static function findMissingNumber($highestNumber, $numbers)
    {
        $lowestMissingNumber = null;
        // Loop from 1 to the highest number
        for ($i = 1; $i <= $highestNumber; $i++) {
            // Check if the current number is missing
            if (! in_array($i, $numbers)) {
                $lowestMissingNumber = $i;
                break;
            }
        }

        return $lowestMissingNumber;
    }
}
