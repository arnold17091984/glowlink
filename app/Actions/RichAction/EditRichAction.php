<?php

namespace App\Actions\RichAction;

use App\DataTransferObjects\RichActionData;
use App\Models\RichAction;

class EditRichAction
{
    public function execute(RichActionData $richData, RichAction $richAction): RichAction
    {
        $richAction->update([
            'layout_id' => $richData->layout_id,
            'type' => $richData->type,
            'label' => $richData->label,
            'link' => $richData->link,
            'text' => $richData->text,
            'model_id' => $richData->model_id,
            'model_type' => $richData->model_type,
        ]);

        return $richAction;
    }
}
