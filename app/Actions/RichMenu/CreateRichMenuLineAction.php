<?php

namespace App\Actions\RichMenu;

use App\Actions\LineMessagingRequest\BuildRichMenuAliasRequestAction;
use App\Actions\LineMessagingRequest\BuildRichMenuRequestAction;
use App\Models\RichMenu;
use LINE\Laravel\Facades\LINEMessagingApi;
use LINE\Laravel\Facades\LINEMessagingBlobApi;

class CreateRichMenuLineAction
{
    public function __construct(
        protected BuildRichMenuRequestAction $buildRichMenuRequestAction,
        protected BuildRichMenuAliasRequestAction $buildRichMenuAliasRequestAction,
        protected GetActionsAction $getActionsAction,
        protected GetTabAction $getTabAction
    ) {
    }

    public function execute(RichMenu $richMenu, ?array $image): void
    {

        $richMenuRequest = $this->buildRichMenuRequestAction->execute($richMenu);

        $richMenuResponse = LINEMessagingApi::createRichMenu($richMenuRequest);

        $richMenuId = $richMenuResponse->getRichMenuId();

        $richMenu->update([
            'reference' => $richMenuId,
        ]);

        $richMenuImage = null;
        if (is_null($image)) {
            $richMenuImage = file_get_contents($richMenu->getFirstMediaUrl('richmenus'));
        } else {
            $richMenuImage = file_get_contents(reset($image)->getPathName());
        }

        LINEMessagingBlobApi::setRichMenuImage($richMenuId, $richMenuImage, null, [], 'image/jpeg');

        if ($richMenu->tab_no == 1) {
            LINEMessagingApi::setDefaultRichMenu($richMenuResponse->getRichMenuId());
        }

        $richAliasRequest = $this->buildRichMenuAliasRequestAction->execute($richMenu->rich_menu_alias, $richMenuId);

        LINEMessagingApi::createRichMenuAlias($richAliasRequest);
    }
}
