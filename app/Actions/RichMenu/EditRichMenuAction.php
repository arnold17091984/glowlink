<?php

namespace App\Actions\RichMenu;

use App\DataTransferObjects\RichMenuData;
use App\Jobs\CreateRichMenuLineJob;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\RichMenu;
use App\Models\RichMenuSet;

class EditRichMenuAction
{
    public function __construct(
        protected DeleteRichMenuLineAction $deleteRichMenuLineAction,
        protected GetActionsAction $getActionsAction,
        protected GetTabAction $getTabAction,
        protected CreateRichMenuLineAction $createRichMenuLineAction
    ) {
    }

    public function execute(RichMenuSet $richMenuSet, RichMenu $richMenu, array $data, ?RichMenu $parent = null): RichMenu
    {

        $tab = [];
        if ((int) $richMenuSet->layout_no !== 1) {
            $tab = $this->getTabAction->execute($data['tab_no'], $richMenuSet->layout_no, $richMenuSet->reference);
        }
        $actions = $this->getActionsAction->execute($data['selected_layout'], $data['actions'], $richMenuSet->layout_no, $parent);
        $areas = array_merge($tab, $actions);
        $richMenuAliasId = strtolower($richMenuSet->reference.'-richmenu-alias-'.($data['tab_no']));
        $richMenuData = RichMenuData::fromArray($data, $areas, $richMenuSet->id, $richMenuAliasId);
        $richMenu->update((array) $richMenuData);
        if ($richMenuSet->is_active) {

            DeleteRichMenuLineJob::dispatch($richMenu);
            if (is_string(reset($richMenuData->image))) {
                CreateRichMenuLineJob::dispatch($richMenu, null);

            } else {
                CreateRichMenuLineJob::dispatch($richMenu, $richMenuData->image);

            }

        }

        return $richMenu;
    }
}
