<?php

namespace App\Actions\Broadcast;

use App\Actions\MessageDelivery\DeleteMessageDeliveriesAction;
use App\Models\Broadcast;

class DeleteBroadcastAction
{
    public function __construct(
        protected DeleteMessageDeliveriesAction $deleteMessageDeliveriesAction
    ) {
    }

    public function execute(Broadcast $broadcast): ?bool
    {
        $this->deleteMessageDeliveriesAction->execute($broadcast, Broadcast::class);

        return $broadcast->delete();
    }
}
