<?php

namespace App\Actions\RichCard;

use App\DataTransferObjects\RichCardData;
use App\Models\RichCard;

class CreateRichCardAction
{
    public function execute(RichCardData $richCardData): RichCard
    {
        $richCard = RichCard::create([
            'name' => $richCardData->name,
            'card' => $richCardData->card,
        ]);

        return $richCard;
    }
}
