<?php

namespace App\Actions\RichMenu;

use LINE\Laravel\Facades\LINEMessagingApi;

class DeleteAllRichMenuLineAction
{
    public function execute(): void
    {
        $resRichMenu = LINEMessagingApi::getRichMenuList();

        foreach ($resRichMenu->getRichmenus() as $richMenu) {
            LINEMessagingApi::deleteRichMenu($richMenu->getRichMenuId());
        }

        $resRichMenuAlias = LINEMessagingApi::getRichMenuAliasList();

        foreach ($resRichMenuAlias->getAliases() as $richMenuAlias) {
            LINEMessagingApi::deleteRichMenuAlias($richMenuAlias->getRichMenuAliasId());
        }
    }
}
