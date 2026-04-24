<?php

namespace Database\Factories;

use App\Enums\FlagEnum;
use App\Models\Friend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Friend>
 */
class FriendFactory extends Factory
{
    protected $model = Friend::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => 'U'.$this->faker->unique()->regexify('[a-f0-9]{32}'),
            'profile_url' => $this->faker->imageUrl(200, 200, 'people'),
            'mark' => FlagEnum::UNRESOLVED,
            'referred_by' => null,
            'points' => 0,
            'referral_count' => 0,
        ];
    }

    public function withPoints(int $points): static
    {
        return $this->state(['points' => $points]);
    }

    public function flagged(FlagEnum $flag): static
    {
        return $this->state(['mark' => $flag]);
    }

    public function referredBy(Friend $referrer): static
    {
        return $this->state(['referred_by' => $referrer->id]);
    }
}
