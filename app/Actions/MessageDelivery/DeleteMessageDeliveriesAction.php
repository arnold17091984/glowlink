<?php

namespace App\Actions\MessageDelivery;

use App\Models\MessageDelivery;
use Illuminate\Database\Eloquent\Model;

class DeleteMessageDeliveriesAction
{
    public function execute(Model $model, $type): void
    {
        MessageDelivery::where('delivery_id', $model->id)->where('delivery_type', $type)->delete();
    }
}
