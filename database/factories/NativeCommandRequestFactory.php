<?php

namespace Database\Factories;

use App\Models\NativeCommandRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NativeCommandRequest>
 */
class NativeCommandRequestFactory extends Factory
{
    protected $model = NativeCommandRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guild_id' => $this->faker->numerify('####################'),
            'channel_id' => $this->faker->numerify('####################'),
            'discord_user_id' => $this->faker->numerify('####################'),
            'message_content' => '!neon ' . $this->faker->sentence(),
            'command' => ['slug' => 'neon', 'content' => $this->faker->sentence()],
            'additional_parameters' => null,
            'status' => 'pending',
            'executed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }
}
