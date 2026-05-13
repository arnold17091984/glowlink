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
                $uri = $this->normaliseUri($reindexedData[$key]['link'] ?? null);
                if ($uri === null) {
                    continue;
                }
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => $uri,
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::PHONE->value) {
                $tel = $this->normalisePhone($reindexedData[$key]['phone'] ?? null);
                if ($tel === null) {
                    continue;
                }
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => 'tel:'.$tel,
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::MAIL->value) {
                $email = trim((string) ($reindexedData[$key]['mail'] ?? ''));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => 'mailto:'.$email,
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::SHARE_OA->value) {
                // RichMenuSet -> LineChannel.basic_id を解決し、
                // LINE 公式の OA 紹介 URI へ変換する。LINE は basic_id に
                // 必ず "@" 接頭辞が付いた形を要求するため、normalize して付け直す。
                // 付けないと LINE アプリ側で「URL を確認してください」エラーになる。
                $basicId = $this->resolveOaBasicId($parent);
                if (! $basicId) {
                    continue;
                }
                $basicId = '@'.ltrim($basicId, '@');
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => 'https://line.me/R/nv/recommendOA/'.$basicId,
                    ],
                ];
            } elseif ($action === RichMenuActionEnum::SHARE_MESSAGE->value) {
                $text = trim((string) ($reindexedData[$key]['share_text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $areas[] = [
                    'bounds' => $bound,
                    'action' => [
                        'type' => 'uri',
                        'uri' => 'https://line.me/R/share?text='.rawurlencode($text),
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
     * LINE Messaging API が受け付ける URI スキームに正規化する。
     * 受け付けるのは http(s):// / line:// / tel: / mailto: のみ。
     * 空・スペースのみ・既知スキーム以外は null を返してそのセルをスキップ。
     */
    private function normaliseUri(?string $raw): ?string
    {
        $uri = trim((string) $raw);
        if ($uri === '') {
            return null;
        }

        // ユーザーが "example.com/foo" のように書いたら https を補完
        if (! preg_match('#^[a-z][a-z0-9+\-.]*:#i', $uri)) {
            $uri = 'https://'.ltrim($uri, '/');
        }

        // LINE が認める scheme のみ通す
        if (! preg_match('#^(https?|line|tel|mailto)://#i', $uri) && ! str_starts_with($uri, 'tel:') && ! str_starts_with($uri, 'mailto:')) {
            return null;
        }

        return $uri;
    }

    /**
     * SHARE_OA 用に basic_id を解決する。
     * 優先順:
     *   1. RichMenu の RichMenuSet に紐づく LineChannel.basic_id
     *   2. デフォルトチャネル (LineChannel::default()) の basic_id
     *   3. (未来) .env の LINE_BOT_BASIC_ID
     */
    private function resolveOaBasicId(?\App\Models\RichMenu $parent = null): ?string
    {
        // 親 RichMenu が分かっている場合はそこから RichMenuSet を辿る
        if ($parent && $parent->richMenuSet && $parent->richMenuSet->lineChannel) {
            $b = trim((string) $parent->richMenuSet->lineChannel->basic_id);
            if ($b !== '') {
                return $b;
            }
        }

        $default = \App\Models\LineChannel::default() ?? \App\Models\LineChannel::where('is_active', true)->first();
        if ($default && $default->basic_id) {
            return trim((string) $default->basic_id);
        }

        return null;
    }

    /**
     * 電話番号を tel: URI 用に整形する。
     * 国際表記 + は維持し、それ以外の数字以外は除去。
     *   "03-1234-5678" -> "0312345678"
     *   "+81 90 1234 5678" -> "+819012345678"
     *   "abc" -> null
     */
    private function normalisePhone(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) < 6) {
            return null;
        }

        return ($hasPlus ? '+' : '').$digits;
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
