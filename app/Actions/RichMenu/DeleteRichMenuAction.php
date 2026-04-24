<?php

namespace App\Actions\RichMenu;

use App\Models\RichMenu;

class DeleteRichMenuAction
{
    public function __construct(
        protected DeleteRichMenuLineAction $deleteRichMenuLineAction,
    ) {
    }

    public function execute(RichMenu $richMenu): ?bool
    {
        $this->deleteRichMenuLineAction->execute($richMenu);

        return $richMenu->delete();
    }
}
