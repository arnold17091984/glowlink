<?php

namespace App\Actions\ScenarioDelivery;

use App\DataTransferObjects\ScenarioDeliveryData;
use App\Enums\ScenarioStatusEnum;
use App\Models\ScenarioDelivery;

class CreateScenarioDeliveryAction
{
    public function execute(ScenarioDeliveryData $scenarioDeliveryData): ScenarioDelivery
    {
        $scenarioDelivery = ScenarioDelivery::create([
            'name' => $scenarioDeliveryData->name,
            'send_to' => $scenarioDeliveryData->send_to,
            'status' => $scenarioDeliveryData->status ?? ScenarioStatusEnum::PENDING,
        ]);

        return $scenarioDelivery;
    }
}
