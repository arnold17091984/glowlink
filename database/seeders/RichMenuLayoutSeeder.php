<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed bounds for the seven supported rich-menu layouts in both
 * variants: with-tab (multi-tab RichMenuSet, layout_no > 1) and
 * without-tab (single-tab, layout_no === 1).
 *
 * Coordinates are in LINE's 2500x1686 pixel space, matching the
 * cell positions drawn in public/layout/richmenu/layout-{1..7}.svg.
 * GetActionsAction::execute() reads the matching row by id:
 *   - with-tab:    selected_layout (1..7)  -> id 1..7
 *   - without-tab: selected_layout + 7     -> id 8..14
 *
 * If this table is empty the action falls back to a generic 2x2 grid,
 * so for any non-default layout (e.g. layout 5 "1 big top + 3 bottom"
 * cells) tap zones drift off the image and LINE users hit the wrong
 * cell, producing URL errors for invalid actions. Keep this seeded.
 */
class RichMenuLayoutSeeder extends Seeder
{
    public function run(): void
    {
        // Cell area heights:
        //   with-tab: cells occupy y=200..1686, so total 1486 high.
        //   without-tab: cells occupy y=0..1686, so total 1686 high.
        $rows = [
            // === with-tab variants (cell area = 2500 x 1486, offset y=200) ===
            ['id' => 1, 'is_with_tab' => 1, 'cells' => $this->layout1(200, 1486)],
            ['id' => 2, 'is_with_tab' => 1, 'cells' => $this->layout2(200, 1486)],
            ['id' => 3, 'is_with_tab' => 1, 'cells' => $this->layout3(200, 1486)],
            ['id' => 4, 'is_with_tab' => 1, 'cells' => $this->layout4(200, 1486)],
            ['id' => 5, 'is_with_tab' => 1, 'cells' => $this->layout5(200, 1486)],
            ['id' => 6, 'is_with_tab' => 1, 'cells' => $this->layout6(200, 1486)],
            ['id' => 7, 'is_with_tab' => 1, 'cells' => $this->layout7(200, 1486)],

            // === without-tab variants (cell area = 2500 x 1686, offset y=0) ===
            ['id' => 8, 'is_with_tab' => 0, 'cells' => $this->layout1(0, 1686)],
            ['id' => 9, 'is_with_tab' => 0, 'cells' => $this->layout2(0, 1686)],
            ['id' => 10, 'is_with_tab' => 0, 'cells' => $this->layout3(0, 1686)],
            ['id' => 11, 'is_with_tab' => 0, 'cells' => $this->layout4(0, 1686)],
            ['id' => 12, 'is_with_tab' => 0, 'cells' => $this->layout5(0, 1686)],
            ['id' => 13, 'is_with_tab' => 0, 'cells' => $this->layout6(0, 1686)],
            ['id' => 14, 'is_with_tab' => 0, 'cells' => $this->layout7(0, 1686)],
        ];

        foreach ($rows as $row) {
            DB::table('rich_menu_layouts')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'bounds' => json_encode(['bounds' => $row['cells']]),
                    'is_with_tab' => $row['is_with_tab'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /** Layout 1: one full-area cell. */
    private function layout1(int $offsetY, int $h): array
    {
        return [['x' => 0, 'y' => $offsetY, 'width' => 2500, 'height' => $h]];
    }

    /** Layout 2: two cells side-by-side. */
    private function layout2(int $offsetY, int $h): array
    {
        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 1250, 'height' => $h],
            ['x' => 1250, 'y' => $offsetY, 'width' => 1250, 'height' => $h],
        ];
    }

    /** Layout 3: two stacked cells (top + bottom). */
    private function layout3(int $offsetY, int $h): array
    {
        $half = intdiv($h, 2);

        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 2500, 'height' => $half],
            ['x' => 0, 'y' => $offsetY + $half, 'width' => 2500, 'height' => $h - $half],
        ];
    }

    /** Layout 4: 1 big left + 2 small right stacked. */
    private function layout4(int $offsetY, int $h): array
    {
        $halfH = intdiv($h, 2);

        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 1666, 'height' => $h],
            ['x' => 1666, 'y' => $offsetY, 'width' => 834, 'height' => $halfH],
            ['x' => 1666, 'y' => $offsetY + $halfH, 'width' => 834, 'height' => $h - $halfH],
        ];
    }

    /** Layout 5: 1 big top + 3 cells in the bottom row. */
    private function layout5(int $offsetY, int $h): array
    {
        $halfH = intdiv($h, 2);

        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 2500, 'height' => $halfH],
            ['x' => 0, 'y' => $offsetY + $halfH, 'width' => 833, 'height' => $h - $halfH],
            ['x' => 833, 'y' => $offsetY + $halfH, 'width' => 833, 'height' => $h - $halfH],
            ['x' => 1666, 'y' => $offsetY + $halfH, 'width' => 834, 'height' => $h - $halfH],
        ];
    }

    /** Layout 6: 2x2 grid. */
    private function layout6(int $offsetY, int $h): array
    {
        $halfH = intdiv($h, 2);

        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 1250, 'height' => $halfH],
            ['x' => 1250, 'y' => $offsetY, 'width' => 1250, 'height' => $halfH],
            ['x' => 0, 'y' => $offsetY + $halfH, 'width' => 1250, 'height' => $h - $halfH],
            ['x' => 1250, 'y' => $offsetY + $halfH, 'width' => 1250, 'height' => $h - $halfH],
        ];
    }

    /** Layout 7: 3x2 grid. */
    private function layout7(int $offsetY, int $h): array
    {
        $halfH = intdiv($h, 2);

        return [
            ['x' => 0, 'y' => $offsetY, 'width' => 833, 'height' => $halfH],
            ['x' => 833, 'y' => $offsetY, 'width' => 833, 'height' => $halfH],
            ['x' => 1666, 'y' => $offsetY, 'width' => 834, 'height' => $halfH],
            ['x' => 0, 'y' => $offsetY + $halfH, 'width' => 833, 'height' => $h - $halfH],
            ['x' => 833, 'y' => $offsetY + $halfH, 'width' => 833, 'height' => $h - $halfH],
            ['x' => 1666, 'y' => $offsetY + $halfH, 'width' => 834, 'height' => $h - $halfH],
        ];
    }
}
