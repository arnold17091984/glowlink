<?php

namespace App\Filament\Resources\RichMenuResource\RelationManagers;

use App\Actions\RichMenu\EditRichMenuAction;
use App\Actions\RichMenu\GetActionsAction;
use App\Actions\RichMenu\GetTabAction;
use App\Enums\SubRichMenuActionEnum;
use App\Filament\Resources\RichMenuResource;
use App\Forms\Components\RichMenuLayout;
use App\Jobs\CreateRichMenuLineJob;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\AutoResponse;
use App\Models\RichMenu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public $selectedTab = 1;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('chatbar_text')->default('Menu')->maxLength(12)->reactive(),
                        Forms\Components\Toggle::make('selected')->label(trans('Initial state of menu')),
                        Forms\Components\Fieldset::make('Layout')->schema([
                            Forms\Components\Repeater::make('actions')
                                ->disabled(function (Get $get) {
                                    if (! $get('image')) {
                                        return true;
                                    }
                                })
                                ->schema([
                                    Forms\Components\Select::make('action')
                                        ->required()
                                        ->reactive()
                                        ->options(SubRichMenuActionEnum::class),
                                    Forms\Components\Select::make('auto_response_id')
                                        ->label('Auto Response')
                                        ->reactive()
                                        ->columnSpanFull()
                                        ->required()
                                        ->visible(fn (Get $get) => $get('action') === SubRichMenuActionEnum::AUTO_RESPONSE->value)
                                        ->options(function () {
                                            return AutoResponse::orderBy('updated_at', 'desc')->pluck('name', 'id');
                                        }),
                                    Forms\Components\TextInput::make('link')->required()->hidden(fn (Get $get) => $get('action') !== SubRichMenuActionEnum::LINK->value),
                                    Forms\Components\Textarea::make('text')->required()->hidden(fn (Get $get) => $get('action') !== SubRichMenuActionEnum::MESSAGE->value),
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
                                ->reorderableWithDragAndDrop(false)
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
                            ->imageEditorViewportWidth('1280')
                            ->imageEditorViewportHeight('863')
                            ->imageCropAspectRatio('1280:863')
                            ->imageResizeTargetWidth('1280')
                            ->imageResizeTargetHeight('863')
                            ->previewable(false)
                            ->acceptedFileTypes(['image/jpeg'])
                            ->disk(env('MEDIA_DISK'))
                            ->collection('richmenus')
                            ->required()
                            ->hintIcon('heroicon-m-question-mark-circle', 'You can upload files up to 1MB. jpg, png images with an image size of 1280 x 863'),
                        RichMenuLayout::make('selected_layout')
                            ->default(1)
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\TextInput::make('tab_no')->label('Enter Tab Number')
                            ->disabled()
                            ->dehydrated()
                            ->default(function ($livewire) {
                                $parentTabNo = $livewire->getOwnerRecord()->tab_no;
                                $childCount = $livewire->getOwnerRecord()->children()->count() + 1;
                                $newTabNo = $parentTabNo.'-'.$childCount;

                                while (RichMenu::whereParentId($this->ownerRecord->id)->where('tab_no', $newTabNo)->exists()) {
                                    $childCount++;
                                    $newTabNo = $parentTabNo.'-'.$childCount;
                                }

                                return $newTabNo;
                            }),
                    ]),
                    Forms\Components\Placeholder::make('')->content(function ($record, $state, $livewire, $set) {
                        $layout_no = (int) $this->ownerRecord->RichMenuSet->layout_no;
                        $selected_layout = $state['selected_layout'];
                        $chatbar_text = $state['chatbar_text'] ?? 'Menu';
                        $tabNumber = $this->ownerRecord->tab_no ?? 1;
                        $tabImage = '';
                        $layoutImage = '<img src="'.
                        asset('layout/richmenu/layout-'.$selected_layout.'.svg').
                        '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        if ($layout_no !== 1) {
                            $tabImage = '<img src="'.
                            asset('layout/tab/tab-'.$layout_no.'/'.RichMenuResource::determineTabLayout($tabNumber).'.svg').
                            '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        } else {
                            $layoutImage = '<img src="'.
                            asset('layout/richmenu/noTab/layout-'.$selected_layout.'.svg').
                            '" style="background-color: rgba(0, 0, 0, 0.2); position: relative; z-index: 2;"  draggable="false"/>';
                        }

                        $image = '';

                        if ($state['image'] && ! $record) {
                            $firstValue = reset($state['image']);
                            $filePath = Storage::disk('s3')->putFile('temp', $firstValue->getRealPath());
                            $image = Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes(15));
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('parent_id')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('tab_no'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Sub Menu')
                    ->modalHeading('Create Sub Menu')
                    ->mutateFormDataUsing(function (array $data): array {

                        $data['rich_menu_set_id'] = $this->ownerRecord->rich_menu_set_id;
                        $tab = [];

                        if ((int) $this->ownerRecord->richMenuSet->layout_no !== 1) {
                            $tab = app(GetTabAction::class)->execute($this->ownerRecord->tab_no, $this->ownerRecord->richMenuSet->layout_no, $this->ownerRecord->richMenuSet->reference);
                        }
                        $actions = app(GetActionsAction::class)->execute($data['selected_layout'], $data['actions'], $this->ownerRecord->richMenuSet->layout_no, $this->ownerRecord);

                        $areas = array_merge($tab, $actions);

                        $data['areas'] = $areas;

                        $data['rich_menu_alias'] = strtolower($this->ownerRecord->richMenuSet->reference.'-richmenu-alias-'.($data['tab_no']));
                        $data['rich_menu_id'] = strtolower($data['rich_menu_alias'].'-richmenu-'.($data['tab_no']));

                        return $data;
                    })->after(function (RichMenu $record) {
                        if ($this->ownerRecord->richMenuSet->is_active) {
                            CreateRichMenuLineJob::dispatch($record, null);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (RichMenu $record, $data): Model {
                        $richMenu = DB::transaction(fn () => app(EditRichMenuAction::class)->execute($this->ownerRecord->richMenuSet, $record, $this->mountedTableActionsData[0], $this->ownerRecord));

                        return $richMenu;
                    })
                    ->mutateFormDataUsing(function (array $data): array {

                        $data['rich_menu_set_id'] = $this->ownerRecord->rich_menu_set_id;
                        $tab = [];

                        if ((int) $this->ownerRecord->richMenuSet->layout_no !== 1) {
                            $tab = app(GetTabAction::class)->execute($this->ownerRecord->tab_no, $this->ownerRecord->richMenuSet->layout_no, $this->ownerRecord->richMenuSet->reference);
                        }
                        $actions = app(GetActionsAction::class)->execute($data['selected_layout'], $data['actions'], $this->ownerRecord->richMenuSet->layout_no, $this->ownerRecord);

                        $areas = array_merge($tab, $actions);

                        $data['areas'] = $areas;

                        $data['rich_menu_alias'] = strtolower($this->ownerRecord->richMenuSet->reference.'-richmenu-alias-'.($data['tab_no']));
                        $data['rich_menu_id'] = strtolower($data['rich_menu_alias'].'-richmenu-'.($data['tab_no']));

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()->before(
                    function ($record) {
                        if ($this->ownerRecord->richMenuSet->is_active) {
                            DeleteRichMenuLineJob::dispatch($record);
                        }
                    }
                ),
            ]);
        // ->bulkActions([
        //     Tables\Actions\BulkActionGroup::make([
        //         Tables\Actions\DeleteBulkAction::make(),
        //     ]),
        // ]);
    }

    public function onClickSelectedLayout()
    {
        // dd($this->mountedTableActionsData[0], $this);
        $this->mountedTableActionsData[0]['selected_layout'] = (int) $this->selectedTab;

        $actionCounts = [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 4, 6 => 4, 7 => 6];

        $selected_layout = $this->mountedTableActionsData[0]['selected_layout'] ?? null;
        $action = $selected_layout ? array_fill(0, $actionCounts[$selected_layout], ['action' => null]) : [];
        $this->mountedTableActionsData[0]['actions'] = $action;
    }

    public function onClickClose()
    {
        $this->selectedTab = $this->mountedTableActionsData[0]['selected_layout'];
    }
}
