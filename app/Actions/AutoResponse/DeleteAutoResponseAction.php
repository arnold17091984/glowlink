<?php

namespace App\Actions\AutoResponse;

use App\Actions\MessageDelivery\DeleteMessageDeliveriesAction;
use App\Models\AutoResponse;

class DeleteAutoResponseAction
{
    public function __construct(
        protected DeleteMessageDeliveriesAction $deleteMessageDeliveriesAction
    ) {
    }

    public function execute(AutoResponse $autoResponse): ?bool
    {
        $this->deleteMessageDeliveriesAction->execute($autoResponse, AutoResponse::class);

        return $autoResponse->delete();
    }
}
