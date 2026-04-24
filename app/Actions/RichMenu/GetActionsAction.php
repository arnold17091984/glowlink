<?php

namespace App\Actions\RichMenu;

use App\Enums\RichMenuActionEnum;
use App\Enums\SubRichMenuActionEnum;
use App\Models\AutoResponse;
use App\Models\RichMenu;
use App\Models\RichMenuLayout;

class GetActionsAction
{
    public function execute($selected, array $actions, int $layout, ?RichMenu $parent = null): array
    {
        if ($layout === 1) {
            $selected = $selected + 7;
        }
        $bounds = RichMenuLayout::whereId($selected)->first()->bounds;
        $areas = [];
        $reindexedData = array_values($actions);
        foreach ($bounds['bounds'] as $key => $bound) {
            if ($reindexedData[$key]['action'] === RichMenuActionEnum::MESSAGE->value) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'message',
                        'text' => $reindexedData[$key]['text'],
                    ],
                ];
            } elseif ($reindexedData[$key]['action'] === RichMenuActionEnum::LINK->value) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => $reindexedData[$key]['link'],
                    ],
                ];
            } elseif ($reindexedData[$key]['action'] === RichMenuActionEnum::AUTO_RESPONSE->value) {
                $autoResponse = AutoResponse::find($reindexedData[$key]['auto_response_id']);
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'message',
                        'text' => $autoResponse->condition[0]['keyword'] ?? '',
                    ],
                ];
            } elseif ($reindexedData[$key]['action'] === RichMenuActionEnum::SUB_MENU->value) {
                $richMenu = RichMenu::find($reindexedData[$key]['children_id']);
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'richmenuswitch',
                        'richMenuAliasId' => $richMenu->rich_menu_alias,
                        'data' => 'richmenu-changed-to-'.($richMenu->tab_no),
                    ],
                ];
            } elseif ($reindexedData[$key]['action'] === SubRichMenuActionEnum::BACK_TO_MAIN->value) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'richmenuswitch',
                        'richMenuAliasId' => $parent->rich_menu_alias,
                        'data' => 'richmenu-changed-to-'.($parent->tab_no),
                    ],
                ];
            } else {
            }
        }

        return $areas;
    }
}
