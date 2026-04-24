<?php

namespace App\Actions\LineMessagingRequest;

use App\Models\RichMenu;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

class BuildRichMenuRequestAction
{
    public function execute(RichMenu $richMenu): RichMenuRequest
    {
        return new RichMenuRequest([
            'size' => [
                'width' => 1280,
                'height' => 863,
            ],
            'selected' => $richMenu->selected,
            'name' => $richMenu->rich_menu_id,
            'chatBarText' => $richMenu->chatbar_text,
            'areas' => $richMenu->areas,
        ]);
    }
}
