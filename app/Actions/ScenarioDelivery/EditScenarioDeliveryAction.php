<?php

namespace App\Actions\ScenarioDelivery;

use App\DataTransferObjects\ScenarioDeliveryData;
use App\Models\ScenarioDelivery;

class EditScenarioDeliveryAction
{
    public function execute(ScenarioDeliveryData $scenarioDeliveryData, ScenarioDelivery $scenarioDelivery): ScenarioDelivery
    {
        $scenarioDelivery->update([
            'name' => $scenarioDeliveryData->name,
            'send_to' => $scenarioDeliveryData->send_to,
            'status' => $scenarioDeliveryData->status,
        ]);

        return $scenarioDelivery;
    }
}
