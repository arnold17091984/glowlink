<?php

namespace App\Actions\RichMenu;

use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\RichMenu;

class DeleteRichMenuLineAction
{
    public function __construct(protected LineGatewayManager $gateways)
    {
    }

    public function execute(RichMenu $richMenu): void
    {
        $channelId = optional($richMenu->richMenuSet)->line_channel_id;
        $gateway = $this->gateways->forChannelId($channelId);

        if ($richMenu->reference) {
            $gateway->deleteRichMenu((string) $richMenu->reference);
        }
        if ($richMenu->rich_menu_alias) {
            $gateway->deleteRichMenuAlias((string) $richMenu->rich_menu_alias);
        }
    }
}
