<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Actions\RichMenu\CreateRichMenuAction;
use App\Filament\Resources\RichMenuResource;
use App\Filament\Traits\HasParentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateRichMenu extends CreateRecord
{
    use HasParentResource;

    protected static string $resource = RichMenuResource::class;

    public $selectedTab = 1;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getParentResource()::getUrl('rich-menus.index', [
            'parent' => $this->parent,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data[$this->getParentRelationshipKey()] = $this->parent->id;

        return $data;
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

    public function handleRecordCreation(array $data): Model
    {
        $richMenu = DB::transaction(
            fn () => app(CreateRichMenuAction::class)
                ->execute($this->data, $this->parent)
        );

        return $richMenu;
    }
}
