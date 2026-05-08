<?php

namespace App\Actions\RichMenu;

use App\Enums\RichMenuActionEnum;
use App\Enums\SubRichMenuActionEnum;
use App\Models\AutoResponse;
use App\Models\RichMenu;
use App\Models\RichMenuLayout;

/**
 * Rich Menu の各タップ領域 (action area) を、選んだセルレイアウトの bounds と
 * ユーザーが入力した action 種別 (link/message/sub_menu/auto_response) で組み立てる。
 *
 * rich_menu_layouts テーブルにシードデータが無い場合でも crash しないように、
 * 標準的なグリッド (2500x1686 を均等分割) でフォールバックする。
 */
class GetActionsAction
{
    public function execute($selected, array $actions, int $layout, ?RichMenu $parent = null): array
    {
        if ($layout === 1) {
            $selected = $selected + 7;
        }

        $row = RichMenuLayout::whereId($selected)->first();
        $boundsList = $row && isset($row->bounds['bounds']) && is_array($row->bounds['bounds'])
            ? $row->bounds['bounds']
            : $this->defaultBounds(count($actions), $layout);

        $areas = [];
        $reindexedData = array_values($actions);

        foreach ($boundsList as $key => $bound) {
            if (! isset($reindexedData[$key])) {
                continue;
            }
            $action = $reindexedData[$key]['action'] ?? null;

            if ($action === RichMenuActionEnum::MESSAGE->value) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'message',
                        'text' => $reindexedData[$key]['text'] ?? '',
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::LINK->value) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => $reindexedData[$key]['link'] ?? 'https://example.com',
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::AUTO_RESPONSE->value) {
                $autoResponse = AutoResponse::find($reindexedData[$key]['auto_response_id'] ?? null);
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'message',
                        'text' => optional($autoResponse)->condition[0]['keyword'] ?? '',
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::SUB_MENU->value) {
                $richMenu = RichMenu::find($reindexedData[$key]['children_id'] ?? null);
                if (! $richMenu) {
                    continue;
                }
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'richmenuswitch',
                        'richMenuAliasId' => $richMenu->rich_menu_alias,
                        'data' => 'richmenu-changed-to-'.($richMenu->tab_no),
                    ],
                ];
            } elseif ($action === SubRichMenuActionEnum::BACK_TO_MAIN->value && $parent) {
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'richmenuswitch',
                        'richMenuAliasId' => $parent->rich_menu_alias,
                        'data' => 'richmenu-changed-to-'.($parent->tab_no),
                    ],
                ];
            }
        }

        return $areas;
    }

    /**
     * rich_menu_layouts シードが無い場合のフォールバック。
     * 2500x1686 を action 数で grid 分割した bounds 配列を返す。
     * タブ付きレイアウト (layout != 1) は上部 200px をタブ領域として控除。
     */
    private function defaultBounds(int $cellCount, int $layoutNo): array
    {
        $totalWidth = 2500;
        $totalHeight = $layoutNo === 1 ? 1686 : 1486;
        $offsetY = $layoutNo === 1 ? 0 : 200;

        if ($cellCount <= 0) {
            return [];
        }

        // 2x3 / 1x3 / 2x2 / 1x1 をいい感じに分割
        $cols = match (true) {
            $cellCount >= 6 => 3,
            $cellCount >= 4 => 2,
            $cellCount >= 2 => $cellCount,
            default => 1,
        };
        $rows = (int) ceil($cellCount / $cols);

        $cellWidth = (int) round($totalWidth / $cols);
        $cellHeight = (int) round($totalHeight / $rows);

        $bounds = [];
        for ($i = 0; $i < $cellCount; $i++) {
            $col = $i % $cols;
            $row = intdiv($i, $cols);
            $bounds[] = [
                'x' => $col * $cellWidth,
                'y' => $offsetY + $row * $cellHeight,
                'width' => $cellWidth,
                'height' => $cellHeight,
            ];
        }

        return $bounds;
    }
}
