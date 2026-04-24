<?php

namespace Database\Factories;

use App\Models\AwardPointsLogs;
use App\Models\Friend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AwardPointsLogs>
 */
class AwardPointsLogsFactory extends Factory
{
    protected $model = AwardPointsLogs::class;

    public function definition(): array
    {
        return [
            'friend_id' => Friend::factory(),
            'awarded_points' => $this->faker->numberBetween(10, 500),
            'reason' => $this->faker->sentence(),
        ];
    }
}
