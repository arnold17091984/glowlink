<?php

namespace App\Actions\Broadcast;

use App\DataTransferObjects\BroadcastData;
use App\Models\Broadcast;

class CreateBroadcastAction
{
    public function execute(BroadcastData $broadcastData): Broadcast
    {
        $broadCast = Broadcast::create((array) $broadcastData);

        return $broadCast;
    }
}
