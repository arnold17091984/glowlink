<?php

namespace App\Actions\Friend;

use App\Enums\AwardTypeEnum;
use App\Models\AwardPointsLogs;
use App\Models\Friend;
use Illuminate\Support\Facades\DB;

class ManagePointsAction
{
    public function execute(array $data, Friend $friend)
    {
        DB::transaction(function () use ($friend, $data) {
            $newPoints = $friend->points + $data['points'];

            $friend->update([
                'points' => $newPoints,
            ]);

            AwardPointsLogs::create([
                'friend_id' => $friend->id,
                'referral_id' => null,
                'awarded_points' => $data['points'],
                'type' => AwardTypeEnum::MANUAL,
                'reason' => $data['reason'],
            ]);

        });

    }
}
