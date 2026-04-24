<?php

namespace Database\Factories;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'message' => $this->faker->sentence(),
            'is_active' => true,
            'link' => 'https://liff.line.me/'.$this->faker->regexify('[a-f0-9]{8}'),
            'referrer_awarded_points' => 100,
            'referral_acceptance_points' => 50,
        ];
    }
}
