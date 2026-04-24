<?php

namespace App\Actions\LineMessagingRequest;

use LINE\Clients\MessagingApi\Model\CreateRichMenuAliasRequest;

class BuildRichMenuAliasRequestAction
{
    public function execute(string $richAliasId, string $richMenuId): CreateRichMenuAliasRequest
    {
        return new CreateRichMenuAliasRequest([
            'richMenuAliasId' => $richAliasId,
            'richMenuId' => $richMenuId,
        ]);
    }
}
