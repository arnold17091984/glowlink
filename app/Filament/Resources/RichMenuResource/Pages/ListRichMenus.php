<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Filament\Resources\RichMenuResource;
use App\Filament\Traits\HasParentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichMenus extends ListRecords
{
    use HasParentResource;

    protected static string $resource = RichMenuResource::class;

    protected function getHeaderActions(): array
    {
        $layoutNo = $this->parent->layout_no;

        if ($layoutNo === count($this->parent->richMenus->filter(function ($record) {
            return $record->parent_id === null;
        })) && $layoutNo !== 4) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->createAnother(false)

                ->url(
                    fn (): string => static::getParentResource()::getUrl('rich-menus.create', [
                        'parent' => $this->parent,
                    ])
                ),
        ];
    }
}
