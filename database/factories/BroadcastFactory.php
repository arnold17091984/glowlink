<?php

namespace Database\Factories;

use App\Enums\RepeatEnum;
use App\Models\Broadcast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Broadcast>
 */
class BroadcastFactory extends Factory
{
    protected $model = Broadcast::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'send_to' => 'all',
            'repeat' => RepeatEnum::ONCE->value,
            'is_send_now' => false,
            'every' => null,
            'start_date' => now()->addMinutes(5),
            'next_date' => null,
            'last_date' => null,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function sendNow(): static
    {
        return $this->state([
            'is_send_now' => true,
            'start_date' => now(),
        ]);
    }

    public function recurring(RepeatEnum $repeat, int $every): static
    {
        return $this->state([
            'repeat' => $repeat->value,
            'every' => $every,
        ]);
    }
}
