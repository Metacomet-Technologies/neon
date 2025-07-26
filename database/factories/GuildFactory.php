<?php

namespace Database\Factories;

use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guild>
 */
class GuildFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Guild::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8, true) . fake()->randomNumber(8, true), // Discord snowflake format
            'name' => fake()->word() . ' ' . fake()->word() . ' Server',
            'icon' => fake()->optional()->md5(),
        ];
    }
}
