<?php

namespace App\Filament\Resources;

use App\Enums\RichActionEnum;
use App\Filament\Resources\RichMessageResource\Pages;
use App\Forms\Components\RichMessageLayout;
use App\Models\AutoResponse;
use App\Models\Coupon;
use App\Models\Referral;
use App\Models\RichMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class RichMessageResource extends Resource
{
    protected static ?string $model = RichMessage::class;

    protected static ?string $navigationGroup = 'Rich Media';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Fieldset::make('Layout')->schema([
                            Forms\Components\Repeater::make('layout')
                                ->schema([
                                    Forms\Components\Select::make('action')
                                        ->required()
                                        ->reactive()
                                        ->columnSpanFull()
                                        ->options(RichActionEnum::class)
                                        ->afterStateUpdated(function ($set) {
                                            $set('link', '');
                                            $set('label', '');
                                            $set('text', '');
                                            $set('model_id', '');
                                        }),
                                    Forms\Components\Select::make('model_id')
                                        ->label('Name')
                                        ->options(function ($get) {
                                            if ($get('action') === RichActionEnum::COUPON->value) {
                                                return Coupon::whereIsActive(true)->orderBy('updated_at', 'desc')->pluck('name', 'id');
                                            }
                                            if ($get('action') === RichActionEnum::REFERRAL->value) {
                                                return Referral::whereIsActive(true)->orderBy('updated_at', 'desc')->pluck('name', 'id');
                                            }
                                        })
                                        ->reactive()
                                        ->visible(fn (Get $get) => $get('action') === RichActionEnum::REFERRAL->value || $get('action') === RichActionEnum::COUPON->value)
                                        ->afterStateUpdated(
                                            function (Set $set, $state, $get) {
                                                if ($get('action') === RichActionEnum::REFERRAL->value) {
                                                    $referral = Referral::find($state);
                                                    $set('text', $referral->message ?? null);
                                                    $set('link', $referral->link ?? null);
                                                }
                                                if ($get('action') === RichActionEnum::COUPON->value) {
                                                    $coupon = Coupon::find($state);
                                                    $couponCode = rawurlencode($coupon->coupon_code);
                                                    $set('link', env('LINE_LIFF_REDEEM').'?couponCode='.$couponCode);
                                                }
                                            }
                                        )
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('link')
                                        ->required()
                                        ->columnSpanFull()
                                        ->readonly(fn (Get $get) => $get('action') === RichActionEnum::REFERRAL->value || $get('action') === RichActionEnum::COUPON->value)
                                        ->visible(fn (Get $get) => $get('action') === RichActionEnum::LINK->value || $get('action') === RichActionEnum::REFERRAL->value || $get('action') === RichActionEnum::COUPON->value),
                                    Forms\Components\Select::make('model_id')
                                        ->label('Auto Response')
                                        ->reactive()
                                        ->columnSpanFull()
                                        ->required()
                                        ->visible(fn (Get $get) => $get('action') === RichActionEnum::AUTO_RESPONSE->value)
                                        ->options(function () {
                                            return AutoResponse::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                        })
                                        ->afterStateUpdated(
                                            function (Set $set, $state) {
                                                $autoResponse = AutoResponse::find($state);

                                                $set('text', $autoResponse->condition[0]['keyword'] ?? '');
                                            }
                                        ),
                                    Forms\Components\Textarea::make('text')
                                        ->required()
                                        ->columnSpanFull()
                                        ->readonly(fn (Get $get) => $get('action') === RichActionEnum::REFERRAL->value)
                                        ->visible(fn (Get $get) => $get('action') === RichActionEnum::MESSAGE->value || $get('action') === RichActionEnum::REFERRAL->value),
                                ])
                                ->itemLabel(function ($uuid, $component) {
                                    $keys = array_keys($component->getState());
                                    $index = array_search($uuid, $keys);
                                    $alphabet = range('A', 'Z');

                                    return $alphabet[$index];
                                })
                                ->formatStateUsing(function ($record, $set) {
                                    $array = [];
                                    if (! $record) {
                                        $array[] = [
                                            'id' => null,
                                            'type' => null,
                                            'text' => null,
                                            'link' => null,
                                            'model_id' => null,
                                        ];
                                    } else {
                                        foreach ($record->layouts as $layout) {
                                            $action = $layout->richAction;
                                            if (is_null($action)) {
                                                $array[] = [
                                                    'id' => null,
                                                    'action' => RichActionEnum::NO_ACTION->value,
                                                    'text' => null,
                                                    'link' => null,
                                                    'model_id' => null,
                                                ];

                                                return $array;
                                            }
                                            $model = $action?->model_type ? $action?->model : null;
                                            $message = $model?->message;
                                            if ($action?->model_type === AutoResponse::class) {
                                                $message = $model->condition[0]['keyword'];
                                            }
                                            $array[] = [
                                                'id' => $action->id,
                                                'action' => $action->type,
                                                'text' => is_null($message) ? $action->text : $message,
                                                'link' => $model->link ?? $action->link,
                                                'model_id' => $action->model_id,
                                            ];
                                        }
                                    }

                                    return $array;
                                })
                                ->disabled(fn (Get $get) => empty($get('image')))
                                ->hiddenLabel()
                                ->deletable(false)
                                ->addable(false)
                                ->reorderableWithDragAndDrop(false)
                                ->collapsible()
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->columnSpan(2),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Layout')->schema([
                            Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                                ->image()
                                ->imageResizeMode('cover')
                                ->imageEditor()
                                ->imageCropAspectRatio('9:9')
                                ->imageResizeTargetWidth('1040')
                                ->imageResizeTargetHeight('1040')
                                ->previewable(false)
                                ->acceptedFileTypes(['image/*'])
                                ->collection('messages')
                                ->disk(env('MEDIA_DISK'))
                                ->required(),
                            RichMessageLayout::make('selected_layout')
                                ->columnSpanFull(),
                        ]),
                        Forms\Components\Placeholder::make('')->content(function ($record, $state) {
                            $selected_layout = $state['selected_layout'] ?? 1;
                            $image = '';
                            if ($state['image'] && ! $record) {
                                $firstValue = reset($state['image']);
                                $filePath = Storage::disk('s3')->putFile('temp', $firstValue->getRealPath());
                                $image = Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes(15));
                            }

                            if ($record) {
                                $image = $record->getFirstMediaUrl('messages');
                            }

                            return new HtmlString('
                            <div style="position: relative">
                                '.(($state['image']) ? '<img src="'.$image.'" style="max-height: 400px; border-radius: 8px; position: absolute; top: 0px; right: 0px; "/>'
                                : '').'

                                <img src="'.asset('layout/richmessage/layout-'.$selected_layout.'.svg').'" style="max-height: 400px; border-radius: 8px; position: absolute; top: 0px; right: 0px; background-color: rgba(0, 0, 0, 0.4)"/>
                            </div>'
                            );
                        }),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')->collection('messages')->sortable(false),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->checkIfRecordIsSelectableUsing(function (RichMessage $record) {

                return ! $record->isUsed();
            })
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index' => Pages\ListRichMessages::route('/'),
            'create' => Pages\CreateRichMessage::route('/create'),
            'edit' => Pages\EditRichMessage::route('/{record}/edit'),
        ];
    }
}
