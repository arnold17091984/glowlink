<?php

namespace App\Actions\RichMenu;

use App\Jobs\CreateRichMenuLineJob;
use App\Jobs\DeleteAllRichMenuLineJob;
use App\Models\RichMenuSet;

class UpdateActiveRichMenuSetAction
{
    public function __construct(
        protected CreateRichMenuLineAction $createRichMenuLineAction,
        protected DeleteRichMenuLineAction $deleteRichMenuLineAction,
    ) {
    }

    public function execute(RichMenuSet $richMenuSet, bool $state): RichMenuSet
    {
        if (! $state) {
            DeleteAllRichMenuLineJob::dispatch();
            $richMenuSet->update(['is_active' => $state]);

            return $richMenuSet;
        }

        $richMenuSets = RichMenuSet::all();

        if (count(RichMenuSet::all()) == 1) {

            $singleRichMenuSet = $richMenuSets[0];

            if (count($singleRichMenuSet->richMenus) === 0) {
                $richMenuSet->update([
                    'is_active' => ! $richMenuSet->is_active,
                ]);

                return $richMenuSet;
            }

            if ($richMenuSet->is_active) {
                DeleteAllRichMenuLineJob::dispatch();
            } else {
                $this->createRichMenu($singleRichMenuSet);
            }

            $richMenuSet->update([
                'is_active' => ! $richMenuSet->is_active,
            ]);

            return $richMenuSet;
        } else {

            DeleteAllRichMenuLineJob::dispatch();
            $this->createRichMenu($richMenuSet);

            RichMenuSet::whereKeyNot($richMenuSet)->update(['is_active' => false]);

            $richMenuSet->update(['is_active' => true]);

            return $richMenuSet;

        }
    }

    public function createRichMenu(RichMenuSet $richMenuSet)
    {
        foreach ($richMenuSet->richMenus as $richMenu) {
            CreateRichMenuLineJob::dispatch($richMenu, null);
        }
    }
}
