<?php

namespace Database\Factories;

use App\Enums\CouponAmountTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'from' => now()->subDay(),
            'till' => now()->addDays(30),
            'description' => $this->faker->sentence(),
            'is_lottery' => false,
            'win_rate' => null,
            'is_limited' => false,
            'no_of_users' => null,
            'unlimited' => false,
            'is_edit_coupon' => false,
            'coupon_code' => Str::upper(Str::random(6)),
            'coupon_type' => CouponTypeEnum::DISCOUNT->value,
            'amount_type' => CouponAmountTypeEnum::FIXED->value,
            'amount' => 500,
            'required_points' => 0,
            'is_active' => true,
        ];
    }

    public function lottery(int $winRate = 50): static
    {
        return $this->state([
            'is_lottery' => true,
            'win_rate' => $winRate,
        ]);
    }

    public function limited(int $users): static
    {
        return $this->state([
            'is_limited' => true,
            'no_of_users' => $users,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(['unlimited' => true]);
    }

    public function expired(): static
    {
        return $this->state([
            'from' => now()->subDays(30),
            'till' => now()->subDay(),
        ]);
    }
}
