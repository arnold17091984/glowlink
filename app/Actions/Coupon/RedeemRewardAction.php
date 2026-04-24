<?php

namespace App\Actions\Coupon;

use App\Actions\LineMessage\PushMessageAction;
use App\Enums\RedeemCouponStatusEnum;
use App\Models\Coupon;
use App\Models\Friend;
use App\Models\FriendCoupon;
use App\Models\Link;
use Illuminate\Support\Facades\DB;

class RedeemRewardAction
{
    public function __construct(
        protected PushMessageAction $pushMessageAction,
    ) {
    }

    public function execute(string $couponCode, string $userId): array
    {
        $coupon = Coupon::whereCouponCode($couponCode)->first();

        $friend = Friend::whereUserId($userId)->first();

        $friendCoupon = FriendCoupon::whereFriendId($friend->id)->whereCouponId($coupon->id);

        // 非抽選クーポンでも result 配列に is_win が必須なので既定値を持たせる
        $isWin = ! $coupon->is_lottery;
        $text = $coupon->coupon_code;
        if ($friendCoupon->exists() && ! $coupon->unlimited && ! $friendCoupon->whereStatus(RedeemCouponStatusEnum::NOT_WON)->exists()) {
            $text = $coupon->coupon_code;

            return [
                'lose_title' => null,
                'title1' => $text,
                'title2' => null,
                'title3' => null,
                'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                'description' => null,
                'is_win' => true,
            ];
        }

        if ($coupon->from > now()) {
            $text = 'Coupon will be available at '.date('F j, Y g:i A', strtotime($coupon->from));

            return [
                'lose_title' => null,
                'title1' => 'このクーポンはまだ有効ではありません。',
                'title2' => '改めてご利用ください。',
                'title3' => null,
                'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                'description' => null,
                'is_win' => false,
            ];
        }

        if ($coupon->till < now()) {
            $text = 'Coupon Expired';

            return [
                'lose_title' => null,
                'title1' => 'このクーポンの有効期限が切れました。',
                'title2' => 'またの機会にご利用ください。',
                'title3' => null,
                'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                'description' => null,
                'is_win' => false,
            ];
        }

        if ($coupon->is_limited) {
            if ($this->isInLimit($coupon)) {
                $text = 'Sorry, The coupon reach its limit and is not available right now';

                return [
                    'lose_title' => null,
                    'title1' => 'このクーポンは上限数に達しましたので、',
                    'title2' => 'ご利用いただけません。',
                    'title3' => 'またの機会にぜひご利用ください。',
                    'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                    'description' => null,
                    'is_win' => false,
                ];
            }
        }

        if ($friend->points < $coupon->required_points) {
            $text = 'Not Enough points!';

            return [
                'lose_title' => null,
                'title1' => 'ポイントが不足しています！',
                'title2' => '必要なポイント数を獲得してから',
                'title3' => '改めてチャレンジしてください！',
                'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                'description' => null,
                'is_win' => false,
            ];
        }

        $status = RedeemCouponStatusEnum::PENDING;

        if ($coupon->unlimited) {
            $status = RedeemCouponStatusEnum::UNLIMITED;
        } else {
            if ($friendCoupon->exists() && $friendCoupon->whereStatus(RedeemCouponStatusEnum::NOT_WON)) {
                $text = 'You have reached the limit for redeeming this code. Please try again later!';

                return [
                    'lose_title' => null,
                    'title1' => '上限回数に達しましたので、',
                    'title2' => 'ご利用いただけません！',
                    'title3' => 'またの機会にご利用ください。',
                    'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
                    'description' => null,
                    'is_win' => false,
                ];
            }
        }

        $friendCoupon = DB::transaction(function () use ($coupon, $friend, $status): FriendCoupon {
            $friendCoupon = FriendCoupon::create([
                'friend_id' => $friend->id,
                'coupon_id' => $coupon->id,
                'status' => $status,
            ]);

            $newPoints = $friend->points - $coupon->required_points;

            $friend->update([
                'points' => $newPoints,
            ]);

            return $friendCoupon;
        });

        if ($coupon->is_lottery) {
            if ($this->isWinner($coupon)) {
                $text = 'Congratulations! You have won the lottery coupon! Here is your code: '.$coupon->coupon_code;
                $description = null;
                $status = RedeemCouponStatusEnum::WON;
                $isWin = true;

                $friendCoupon->update([
                    'status' => $status,
                ]);

                $link = Link::whereSlug('redeem-form')->first();

                $sendMessage = $coupon->description.' '.$link->url;

                $this->pushMessageAction->execute($sendMessage, $friend);

            } else {
                $text = '残念…ハズレです！';
                $description = '<p>残念、外れてしまいました</p><p>またの機会に是非チャレンジしてください！</p>';
                $status = RedeemCouponStatusEnum::NOT_WON;
                $isWin = false;

                $friendCoupon->update([
                    'status' => $status,
                ]);

                return [
                    'lose_title' => $text,
                    'title1' => null,
                    'title2' => null,
                    'title3' => null,
                    'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/fukuro%402x.png',
                    'description' => $description,
                    'is_win' => $isWin,
                ];
            }
        }

        return [
            'lose_title' => null,
            'title1' => $text,
            'title2' => null,
            'title3' => null,
            'imageUrl' => 'https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png',
            'description' => $coupon->description,
            'is_win' => $isWin,
        ];

    }

    private function isWinner(Coupon $coupon): bool
    {
        if ($coupon->is_lottery) {
            $randomNumber = mt_rand(0, 100);

            if ($randomNumber <= $coupon->win_rate) {
                return true;
            }
        }

        return false;
    }

    private function isInLimit(Coupon $coupon): bool
    {
        if ($coupon->is_limited) {
            $count = FriendCoupon::whereCouponId($coupon->id)->count();

            if ($coupon->is_lottery) {
                $count = FriendCoupon::whereCouponId($coupon->id)->whereStatus(RedeemCouponStatusEnum::WON)->count();
            }

            if ($count >= $coupon->no_of_users) {
                return true;
            }
        }

        return false;
    }
}
