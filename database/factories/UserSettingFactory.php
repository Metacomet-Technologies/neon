<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSetting>
 */
class UserSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme' => 'light',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (UserSetting $userSetting) {
            $user = User::factory()->create();
            $userSetting->user_id = $user->id;
        });
    }
}
