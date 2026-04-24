<?php

namespace App\Actions\RichMenu;

use App\DataTransferObjects\RichMenuData;
use App\Models\RichMenu;
use App\Models\RichMenuSet;

class CreateRichMenuAction
{
    public function __construct(
        protected GetActionsAction $getActionsAction,
        protected GetTabAction $getTabAction,
        protected CreateRichMenuLineAction $createRichMenuLineAction,
    ) {
    }

    public function execute(array $data, RichMenuSet $richMenuSet): RichMenu
    {
        $tab = [];
        if ((int) $richMenuSet->layout_no !== 1) {
            $tab = $this->getTabAction->execute($data['tab_no'], $richMenuSet->layout_no, $richMenuSet->reference);
        }

        $actions = $this->getActionsAction->execute($data['selected_layout'], $data['actions'], $richMenuSet->layout_no);

        $areas = array_merge($tab, $actions);
        $richMenuAliasId = strtolower($richMenuSet->reference.'-richmenu-alias-'.($data['tab_no']));
        $richMenuData = RichMenuData::fromArray($data, $areas, $richMenuSet->id, $richMenuAliasId);
        $richMenu = RichMenu::create((array) $richMenuData);

        if ($richMenuSet->is_active) {
            $this->createRichMenuLineAction->execute($richMenu, $richMenuData->image);
        }

        return $richMenu;
    }
}
