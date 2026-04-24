<?php

namespace App\Actions\RichMenu;

use App\Models\RichMenu;
use LINE\Laravel\Facades\LINEMessagingApi;

class DeleteRichMenuLineAction
{
    public function execute(RichMenu $richMenu): void
    {
        LINEMessagingApi::deleteRichMenu($richMenu->reference);
        LINEMessagingApi::deleteRichMenuAlias($richMenu->rich_menu_alias);
    }
}
