<?php

namespace App\Actions\RichMenu;

use App\Models\TabBounds;

/**
 * Rich Menu のタブ切替領域 (richmenuswitch) を構築する。
 *
 * tab_bounds テーブルが空 (シード未投入) でも 500 にならないように、
 * LINE 公式 2500x1686 座標系で計算した default bounds をフォールバックする。
 */
class GetTabAction
{
    public function execute(string $tabNo, int $layoutNo, string $reference): array
    {
        $areas = [];
        $tabNumbers = [];

        $areas[] = [
            'bounds' => $this->resolveBounds((int) $tabNo, $layoutNo),
            'action' => [
                'type' => 'richmenuswitch',
                'richMenuAliasId' => strtolower($reference.'-richmenu-alias-'.($tabNo)),
                'data' => 'richmenu-changed-to-'.($tabNo),
            ],
        ];

        if ($layoutNo === 2) {
            if ($tabNo == 1) {
                $tabNumbers = [2];
            } elseif ($tabNo == 2) {
                $tabNumbers = [1];
            }
        } elseif ($layoutNo === 3) {
            if ($tabNo == 1) {
                $tabNumbers = [2, 3];
            } elseif ($tabNo == 2) {
                $tabNumbers = [1, 3];
            } elseif ($tabNo == 3) {
                $tabNumbers = [1, 2];
            }
        } elseif ($layoutNo === 4) {
            if ($tabNo == 1) {
                $tabNumbers = [2, 3];
            } elseif ($tabNo == 2) {
                $tabNumbers = [1, 3];
            } elseif ($tabNo % 2 == 0) {
                $tabNumbers = [4, 5, 7];

                foreach ($tabNumbers as $tabNumber) {
                    $nextTabNo = 1;
                    if ($tabNumber === 4) {
                        $nextTabNo = -2;
                    }
                    if ($tabNumber === 7) {
                        $nextTabNo = -1;
                    }

                    $areas[] = [
                        'bounds' => $this->resolveBounds($tabNumber, $layoutNo),
                        'action' => [
                            'type' => 'richmenuswitch',
                            'richMenuAliasId' => strtolower($reference.'-richmenu-alias-'.($tabNo + $nextTabNo)),
                            'data' => 'richmenu-changed-to-'.($tabNo + $nextTabNo),
                        ],
                    ];
                }

                return $areas;
            } else {
                $tabNumbers = [4, 6, 7];

                foreach ($tabNumbers as $tabNumber) {
                    $nextTabNo = 1;
                    if ($tabNumber === 4) {
                        $nextTabNo = -1;
                    }
                    if ($tabNumber === 7) {
                        $nextTabNo = +2;
                    }

                    $areas[] = [
                        'bounds' => $this->resolveBounds($tabNumber, $layoutNo),
                        'action' => [
                            'type' => 'richmenuswitch',
                            'richMenuAliasId' => strtolower($reference.'-richmenu-alias-'.($tabNo + $nextTabNo)),
                            'data' => 'richmenu-changed-to-'.($tabNo + $nextTabNo),
                        ],
                    ];
                }

                return $areas;
            }
        }

        foreach ($tabNumbers as $tabNumber) {
            $areas[] = [
                'bounds' => $this->resolveBounds($tabNumber, $layoutNo),
                'action' => [
                    'type' => 'richmenuswitch',
                    'richMenuAliasId' => strtolower($reference.'-richmenu-alias-'.$tabNumber),
                    'data' => 'richmenu-changed-to-'.$tabNumber,
                ],
            ];
        }

        return $areas;
    }

    /**
     * tab_bounds テーブルから取得 → 無ければ default を計算。
     * LINE 公式 2500x1686 座標系。タブは画面上部 200px 高に配置。
     */
    private function resolveBounds(int $tabNo, int $layoutNo): array
    {
        $row = TabBounds::whereTabNo($tabNo)->whereLayoutNo($layoutNo)->first();
        if ($row && is_array($row->bounds)) {
            return $row->bounds;
        }

        return $this->defaultTabBounds($tabNo, $layoutNo);
    }

    private function defaultTabBounds(int $tabNo, int $layoutNo): array
    {
        $width = 2500;
        $tabHeight = 200;

        $tabsCount = match ($layoutNo) {
            2 => 2,
            3 => 3,
            4 => 7,        // タブ多段 (LINE 標準は 4 までだがアプリは 7 想定)
            default => 1,
        };

        $tabWidth = (int) round($width / max($tabsCount, 1));
        $index = max($tabNo - 1, 0);

        return [
            'x' => $index * $tabWidth,
            'y' => 0,
            'width' => $tabWidth,
            'height' => $tabHeight,
        ];
    }
}
