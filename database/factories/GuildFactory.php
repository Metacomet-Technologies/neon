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
            'id' => $this->faker->unique()->numerify('####################'), // Discord snowflake format
            'name' => $this->faker->words(2, true) . ' Server',
            'icon' => $this->faker->optional()->md5(),
        ];
    }
}
