<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\License>
 */
class LicenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = License::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement([License::TYPE_SUBSCRIPTION, License::TYPE_LIFETIME]),
            'stripe_id' => fake()->boolean(70) ? 'sub_' . fake()->bothify('??????????????') : null,
            'status' => License::STATUS_PARKED,
            'assigned_guild_id' => null,
            'last_assigned_at' => null,
        ];
    }

    /**
     * Indicate that the license is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => License::STATUS_ACTIVE,
            'assigned_guild_id' => fake()->numerify('##################'),
            'last_assigned_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the license is parked.
     */
    public function parked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => License::STATUS_PARKED,
            'assigned_guild_id' => null,
            'last_assigned_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-90 days', '-1 day') : null,
        ]);
    }

    /**
     * Indicate that the license is on cooldown (recently unassigned).
     */
    public function onCooldown(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => License::STATUS_PARKED,
            'assigned_guild_id' => null,
            'last_assigned_at' => fake()->dateTimeBetween('-7 days', '-1 hour'),
        ]);
    }

    /**
     * Indicate that the license is a subscription type.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => License::TYPE_SUBSCRIPTION,
            'stripe_id' => 'sub_' . fake()->bothify('??????????????'),
        ]);
    }

    /**
     * Indicate that the license is a lifetime type.
     */
    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => License::TYPE_LIFETIME,
            'stripe_id' => fake()->boolean(30) ? 'pi_' . fake()->bothify('??????????????') : null,
        ]);
    }

    /**
     * Indicate that the license belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the license is assigned to a specific guild.
     */
    public function assignedToGuild(string $guildId): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => License::STATUS_ACTIVE,
            'assigned_guild_id' => $guildId,
            'last_assigned_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create a license without Stripe ID (manual/promotional license).
     */
    public function promotional(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_id' => null,
        ]);
    }

    /**
     * Indicate that the license is unassigned.
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => License::STATUS_PARKED,
            'assigned_guild_id' => null,
        ]);
    }
}
