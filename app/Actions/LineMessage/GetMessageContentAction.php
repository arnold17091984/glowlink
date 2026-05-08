<?php

namespace App\Actions\LineMessage;

use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\LineChannel;

class GetMessageContentAction
{
    public function __construct(protected LineGatewayManager $gateways)
    {
    }

    public function execute(int|string $messageId, ?LineChannel $channel = null): string
    {
        $gateway = $channel
            ? $this->gateways->forChannel($channel)
            : $this->gateways->default();

        return $gateway->getMessageContent((string) $messageId);
    }
}
