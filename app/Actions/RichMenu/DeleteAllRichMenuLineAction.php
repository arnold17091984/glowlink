<?php

namespace App\Actions\RichMenu;

use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\LineChannel;

/**
 * 指定チャネル (または default) の Rich Menu と Alias を全削除する。
 * Web UI から実行する破壊的操作なので、複数チャネル時は明示的にチャネルを渡す。
 */
class DeleteAllRichMenuLineAction
{
    public function __construct(protected LineGatewayManager $gateways)
    {
    }

    public function execute(?LineChannel $channel = null): void
    {
        $gateway = $channel
            ? $this->gateways->forChannel($channel)
            : $this->gateways->default();

        foreach ($gateway->getRichMenuList() as $richMenu) {
            $id = method_exists($richMenu, 'getRichMenuId') ? $richMenu->getRichMenuId() : ($richMenu->richMenuId ?? null);
            if ($id) {
                $gateway->deleteRichMenu($id);
            }
        }

        foreach ($gateway->getRichMenuAliasList() as $alias) {
            $id = method_exists($alias, 'getRichMenuAliasId') ? $alias->getRichMenuAliasId() : ($alias->richMenuAliasId ?? null);
            if ($id) {
                $gateway->deleteRichMenuAlias($id);
            }
        }
    }
}
