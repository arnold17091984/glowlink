<?php

namespace App\Actions\AutoResponse;

use App\DataTransferObjects\AutoResponseData;
use App\Models\AutoResponse;

class CreateAutoResponseAction
{
    public function execute(AutoResponseData $autoResponseData): AutoResponse
    {
        $autoResponse = AutoResponse::create([
            'name' => $autoResponseData->name,
            'is_active' => $autoResponseData->is_active,
            'condition' => $autoResponseData->condition,
        ]);

        return $autoResponse;
    }
}
