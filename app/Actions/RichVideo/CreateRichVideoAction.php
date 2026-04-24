<?php

namespace App\Actions\RichVideo;

use App\DataTransferObjects\RichVideoData;
use App\Models\RichVideo;

class CreateRichVideoAction
{
    public function execute(RichVideoData $richVideoData): RichVideo
    {
        $richVideo = RichVideo::create((array) $richVideoData);

        return $richVideo;
    }
}
