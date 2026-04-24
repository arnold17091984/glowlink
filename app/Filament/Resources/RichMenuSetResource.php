<?php

namespace App\Filament\Resources;

use App\Actions\RichMenu\UpdateActiveRichMenuSetAction;
use App\Enums\RichMenuTabLayoutEnum;
use App\Filament\Resources\RichMenuResource\Pages\CreateRichMenu;
use App\Filament\Resources\RichMenuResource\Pages\EditRichMenu;
use App\Filament\Resources\RichMenuResource\Pages\ListRichMenus;
use App\Filament\Resources\RichMenuSetResource\Pages;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\RichMenuSet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class RichMenuSetResource extends Resource
{
    protected static ?string $model = RichMenuSet::class;

    protected static ?string $navigationGroup = 'リッチコンテンツ';

    protected static ?string $navigationLabel = 'リッチメニュー';

    protected static ?string  = 'リッチメニュー';

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
                    // Forms\Components\Toggle::make('is_active')->disabled()->label(trans('Active'))->required(),
                    Forms\Components\Section::make([
                        Forms\Components\Radio::make('layout_no')->default(1)->label('')
                            ->options(RichMenuTabLayoutEnum::class)
                            ->descriptions(RichMenuTabLayoutEnum::getDescription()),
                    ]),
                ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\SpatieMediaLibraryImageColumn::make('richMenus.image')
                    ->collection('richmenus')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('layout_no')->label(trans('Layout No')),
                Tables\Columns\TextColumn::make('rich_menus_count')->counts('richMenus')->label(trans('Item count')),
                Tables\Columns\ToggleColumn::make('is_active')->label(trans('Enable'))
                    ->updateStateUsing(function (RichMenuSet $record, $state) {

                        return app(UpdateActiveRichMenuSetAction::class)->execute($record, $state);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)])
            ->defaultSort('created_at', 'desc')->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make(),
                Action::make('Rich Menu List')
                    ->color('success')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(
                        fn (RichMenuSet $record): string => static::getUrl('rich-menus.index', [
                            'parent' => $record->id,
                        ])
                    ), ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()->using(function (Collection $records) {
                    try {
                        foreach ($records as $record) {
                            foreach ($record->richMenus as $richMenu) {
                                if ($record->is_active) {
                                    DeleteRichMenuLineJob::dispatch($richMenu);
                                }
                                $richMenu->delete();
                            }

                            return $record->delete();
                        }
                    } catch (LogicException) {
                        return false;
                    }
                }),
            ])]);
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
            'index' => Pages\ListRichMenuSets::route('/'),
            'create' => Pages\CreateRichMenuSet::route('/create'),
            'edit' => Pages\EditRichMenuSet::route('/{record}/edit'),
            'rich-menus.index' => ListRichMenus::route('/{parent}/rich-menus'),
            'rich-menus.create' => CreateRichMenu::route('/{parent}/rich-menus/create'),
            'rich-menus.edit' => EditRichMenu::route('/{parent}/rich-menus/{record}/edit'),
        ];
    }
}
