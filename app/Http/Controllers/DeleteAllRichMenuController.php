<?php

namespace App\Http\Controllers;

use App\Actions\AutoResponse\AutoResponseAction;
use App\Actions\Friend\StoreFriendAction;
use App\Actions\LineMessage\GetMessageContentAction;
use App\Actions\Talk\StoreChatAction;
use LINE\Laravel\Facades\LINEMessagingApi;
use Spatie\RouteAttributes\Attributes\Route;

class DeleteAllRichMenuController extends Controller
{
    // public function __construct(
    //     protected StoreChatAction $storeChatAction,
    //     protected StoreFriendAction $storeFriendAction,
    //     protected AutoResponseAction $autoResponseAction,
    //     protected GetMessageContentAction $getMessageContentAction
    // ) {
    // }

    #[Route('DELETE', '/RichMenu')]
    public function __invoke()
    {
        $res = LINEMessagingApi::getRichMenuList();

        foreach ($res->getRichmenus() as $richMenu) {
            LINEMessagingApi::deleteRichMenu($richMenu->getRichMenuId());
        }

        $resRichMenuAlias = LINEMessagingApi::getRichMenuAliasList();

        foreach ($resRichMenuAlias->getAliases() as $richMenuAlias) {
            LINEMessagingApi::deleteRichMenuAlias($richMenuAlias->getRichMenuAliasId());
        }
    }
}
