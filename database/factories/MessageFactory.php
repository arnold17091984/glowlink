<?php

namespace Database\Factories;

use App\Enums\MessagingTypeEnum;
use App\Enums\UsedForEnum;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => MessagingTypeEnum::TEXT->value,
            'used_for' => UsedForEnum::AUTO_RESPONSE->value,
            'message' => $this->faker->sentence(),
        ];
    }

    public function text(string $content): static
    {
        return $this->state([
            'type' => MessagingTypeEnum::TEXT->value,
            'message' => $content,
        ]);
    }

    public function image(): static
    {
        return $this->state([
            'type' => MessagingTypeEnum::IMAGE->value,
            'message' => null,
        ]);
    }
}
