<?php

namespace Database\Factories;

use App\Enums\RedeemCouponStatusEnum;
use App\Models\Coupon;
use App\Models\Friend;
use App\Models\FriendCoupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FriendCoupon>
 */
class FriendCouponFactory extends Factory
{
    protected $model = FriendCoupon::class;

    public function definition(): array
    {
        return [
            'friend_id' => Friend::factory(),
            'coupon_id' => Coupon::factory(),
            'status' => RedeemCouponStatusEnum::PENDING->value,
        ];
    }

    public function won(): static
    {
        return $this->state(['status' => RedeemCouponStatusEnum::WON->value]);
    }

    public function notWon(): static
    {
        return $this->state(['status' => RedeemCouponStatusEnum::NOT_WON->value]);
    }
}
