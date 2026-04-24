<?php

namespace App\Actions\ScenarioDelivery;

use App\Actions\MessageDelivery\DeleteMessageDeliveriesAction;
use App\Models\ScenarioDelivery;

class DeleteScenarioDeliveryAction
{
    public function __construct(
        protected DeleteMessageDeliveriesAction $deleteMessageDeliveriesAction
    ) {
    }

    public function execute(ScenarioDelivery $scenarioDelivery): ?bool
    {
        $this->deleteMessageDeliveriesAction->execute($scenarioDelivery, ScenarioDelivery::class);

        return $scenarioDelivery->delete();
    }
}
