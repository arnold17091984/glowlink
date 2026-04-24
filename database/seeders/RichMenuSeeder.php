<?php

namespace Database\Seeders;

use App\Models\RichMenuLayout;
use App\Models\TabBounds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RichMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 2; $i <= 4; $i++) {
            $tab = File::get(public_path('json/tab/Tab'.$i.'.json'));
            $tabData = json_decode($tab, true);

            foreach ($tabData['bounds'] as $index => $bound) {
                TabBounds::create([
                    'tab_no' => $index + 1,
                    'layout_no' => $tabData['layout'],
                    'bounds' => $bound,
                ]);
            }
        }

        for ($i = 1; $i <= 7; $i++) {
            $layout = File::get(public_path('json/richmenu/layout'.$i.'.json'));
            $tabData = json_decode($layout, true);

            RichMenuLayout::create([
                'bounds' => $tabData,
                'is_with_tab' => true,
            ]);
        }

        for ($i = 1; $i <= 7; $i++) {
            $layout = File::get(public_path('json/richmenu/noTab/layout'.$i.'.json'));
            $tabData = json_decode($layout, true);

            RichMenuLayout::create([
                'bounds' => $tabData,
                'is_with_tab' => false,
            ]);
        }

    }
}
