<?php

namespace App\Actions\RichMenu;

use App\Models\TabBounds;

class GetTabAction
{
    public function execute(string $tabNo, int $layoutNo, string $reference): array
    {
        $areas = [];
        $tabNumbers = [];

        $areas[] = [
            'bounds' => TabBounds::whereTabNo($tabNo)->whereLayoutNo($layoutNo)->first()->bounds,
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
                        'bounds' => TabBounds::whereTabNo($tabNumber)->whereLayoutNo($layoutNo)->first()->bounds,
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
                        'bounds' => TabBounds::whereTabNo($tabNumber)->whereLayoutNo($layoutNo)->first()->bounds,
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
                'bounds' => TabBounds::whereTabNo($tabNumber)->whereLayoutNo($layoutNo)->first()->bounds,
                'action' => [
                    'type' => 'richmenuswitch',
                    'richMenuAliasId' => strtolower($reference.'-richmenu-alias-'.$tabNumber),
                    'data' => 'richmenu-changed-to-'.$tabNumber,
                ],
            ];
        }

        return $areas;
    }
}
