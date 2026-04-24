<?php

namespace App\Actions\AutoResponse;

use App\DataTransferObjects\AutoResponseData;
use App\Models\AutoResponse;

class EditAutoResponseAction
{
    public function execute(AutoResponseData $autoResponseData, AutoResponse $autoResponse): AutoResponse
    {
        $autoResponse->update([
            'name' => $autoResponseData->name,
            'is_active' => $autoResponseData->is_active,
            'condition' => $autoResponseData->condition,
        ]);

        return $autoResponse;
    }
}
