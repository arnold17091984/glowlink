<?php

namespace App\Actions\Referral;

use App\DataTransferObjects\ReferralData;
use App\Enums\AwardTypeEnum;
use App\Enums\FlagEnum;
use App\Models\AwardPointsLogs;
use App\Models\Friend;
use App\Models\Referral;
use Exception;
use Illuminate\Support\Facades\DB;

class ReferralAction
{
    public function execute(ReferralData $referralData)
    {
        $referral = Referral::whereName($referralData->referral_name)->first();
        if ($referralData->user_id === $referralData->referred_by) {
            throw new Exception('You can\'t refer yourself');
        }

        $referrer = Friend::whereUserId($referralData->referred_by)->first();
        $user = Friend::whereUserId($referralData->user_id)->first();

        if ($user && $user->referred_by) {
            throw new Exception('This user has already been referred.');
        }

        if ($user) {
            throw new Exception('This user has already been registered.');
        }

        DB::transaction(function () use ($referrer, $referralData, $referral) {

            $friend = Friend::updateOrCreate(
                ['user_id' => $referralData->user_id],
                [
                    'name' => $referralData->name,
                    'profile_url' => $referralData->profile_url,
                    'mark' => FlagEnum::UNRESOLVED,
                ]
            );

            $referrer->increment('referral_count');
            $referrer->increment('points', $referral->referrer_awarded_points);

            $friend->increment('points', $referral->referral_acceptance_points);

            $friend->update([
                'referred_by' => $referrer->id,
            ]);

            AwardPointsLogs::create([
                'friend_id' => $friend->id,
                'referral_id' => $referral->id,
                'awarded_points' => $referral->referral_acceptance_points,
                'type' => AwardTypeEnum::REFERRAL_ACCEPTANCE,
                'reason' => null,
            ]);

            AwardPointsLogs::create([
                'friend_id' => $referrer->id,
                'referral_id' => $referral->id,
                'awarded_points' => $referral->referrer_awarded_points,
                'type' => AwardTypeEnum::REFERRAL,
                'reason' => null,
            ]);

        });
    }
}
