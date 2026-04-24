<?php

namespace App\Actions\Layout;

use App\DataTransferObjects\LayoutData;
use App\Models\Layout;

class CreateLayoutAction
{
    public function execute(LayoutData $layoutData): Layout
    {
        return Layout::create([
            'rich_id' => $layoutData->rich_id,
            'rich_type' => $layoutData->rich_type,
            'x' => $layoutData->x,
            'y' => $layoutData->y,
            'width' => $layoutData->width,
            'height' => $layoutData->height,
            'offsetTop' => $layoutData->offsetTop,
            'offsetBottom' => $layoutData->offsetBottom,
            'offsetStart' => $layoutData->offsetStart,
            'offsetEnd' => $layoutData->offsetEnd,
        ]);
    }
}
