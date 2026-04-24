<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Actions\RichMenu\EditRichMenuAction;
use App\Filament\Resources\RichMenuResource;
use App\Filament\Traits\HasParentResource;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\RichMenu;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class EditRichMenu extends EditRecord
{
    use HasParentResource;

    protected static string $resource = RichMenuResource::class;

    public $selectedTab = 1;

    protected function beforeFill(): void
    {
        $this->selectedTab = $this->record->selected_layout;
    }

    public function onClickSelectedLayout($path)
    {
        $this->data['selected_layout'] = (int) $this->selectedTab;

        $actionCounts = [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 4, 6 => 4, 7 => 6];

        $selected_layout = $this->data['selected_layout'] ?? null;
        $action = $selected_layout ? array_fill(0, $actionCounts[$selected_layout], ['action' => null]) : [];

        $this->data['actions'] = $action;
    }

    public function onClickClose($path)
    {
        $this->selectedTab = $this->data['selected_layout'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->using(function (RichMenu $record) {
                try {
                    DeleteRichMenuLineJob::dispatch($record);

                    return $record->delete();
                } catch (LogicException) {
                    return false;
                }
            }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getParentResource()::getUrl('rich-menus.index', [
            'parent' => $this->parent,
        ]);
    }

    protected function configureDeleteAction(Actions\DeleteAction $action): void
    {
        $resource = static::getResource();

        $action->authorize($resource::canDelete($this->getRecord()))
            ->successRedirectUrl(static::getParentResource()::getUrl('rich-menus.index', [
                'parent' => $this->parent,
            ]));

    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $richMenu = DB::transaction(
            fn () => app(EditRichMenuAction::class)->execute($this->parent, $record, $this->data));

        return $richMenu;
    }
}
