<?php

namespace App\Actions\Broadcast;

use App\DataTransferObjects\BroadcastData;
use App\Models\Broadcast;

class EditBroadcastAction
{
    public function execute(BroadcastData $broadcastData, Broadcast $broadcast): Broadcast
    {
        $broadcast->update((array) $broadcastData);

        return $broadcast;
    }
}
