<?php

namespace Database\Seeders;

use App\Models\LayoutRichMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LayoutRichMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $json = File::get(public_path('json/richmessage/layout'.$i.'.json'));

            $layoutData = json_decode($json, true);

            LayoutRichMessage::create([
                'layout' => $layoutData,
            ]);
        }
    }
}
