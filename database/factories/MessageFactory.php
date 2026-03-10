<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'content' => fake()->sentence(),
            'is_read' => false,
        ];
    }

    /**
     * Indicate that the message has been read.
     *
     * @return static
     */
    public function read()
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }
}
