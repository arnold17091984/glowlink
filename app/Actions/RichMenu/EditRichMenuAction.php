<?php

namespace App\Actions\RichMenu;

use App\DataTransferObjects\RichMenuData;
use App\Jobs\CreateRichMenuLineJob;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\RichMenu;
use App\Models\RichMenuSet;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

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
            // 旧実装は dispatch を 2回別々に呼んでおり、queue の実行順次第で
            // Create→Delete の順に処理されると新メニューが消える事故があった。
            // Bus::chain で「Delete 完了 → Create」を順次保証する。
            $createImage = is_string(reset($richMenuData->image)) ? null : $richMenuData->image;

            try {
                Bus::chain([
                    new DeleteRichMenuLineJob($richMenu),
                    new CreateRichMenuLineJob($richMenu, $createImage),
                ])->dispatch();
            } catch (\Throwable $e) {
                Log::warning('EditRichMenuAction: Bus::chain dispatch failed, fallback to chained syncing', [
                    'rich_menu_id' => $richMenu->id,
                    'error' => $e->getMessage(),
                ]);
                // 最低限の保険として「Delete を先にディスパッチし、then を chain で Create」
                DeleteRichMenuLineJob::dispatch($richMenu)->chain([
                    new CreateRichMenuLineJob($richMenu, $createImage),
                ]);
            }
        }

        return $richMenu;
    }
}
