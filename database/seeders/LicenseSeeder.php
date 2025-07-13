<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\License;
use App\Models\User;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users or create some if none exist
        $users = User::all();
        if ($users->isEmpty()) {
            $users = User::factory(5)->create();
        }

        // Create various types of licenses for testing
        foreach ($users as $user) {
            // Give each user 1-3 licenses
            $licenseCount = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $licenseCount; $i++) {
                $this->createRandomLicense($user);
            }
        }

        // Create some specific test scenarios
        $testUser = $users->first();

        // Active subscription license assigned to a guild
        License::factory()
            ->subscription()
            ->active()
            ->assignedToGuild('123456789012345678')
            ->forUser($testUser)
            ->create();

        // Parked lifetime license
        License::factory()
            ->lifetime()
            ->parked()
            ->forUser($testUser)
            ->create();

        // Promotional license (no Stripe ID)
        License::factory()
            ->promotional()
            ->lifetime()
            ->active()
            ->assignedToGuild('876543210987654321')
            ->forUser($testUser)
            ->create();

        // License on cooldown
        License::factory()
            ->onCooldown()
            ->subscription()
            ->forUser($testUser)
            ->create();
    }

    /**
     * Create a random license for a user.
     */
    private function createRandomLicense(User $user): void
    {
        $factory = License::factory()->forUser($user);

        // Random type
        $factory = fake()->boolean() ? $factory->subscription() : $factory->lifetime();

        // Random status/state
        $rand = fake()->numberBetween(1, 100);
        if ($rand <= 50) {
            $factory = $factory->active();
        } elseif ($rand <= 80) {
            $factory = $factory->parked();
        } else {
            $factory = $factory->onCooldown();
        }

        // Sometimes make it promotional
        if (fake()->boolean(20)) {
            $factory = $factory->promotional();
        }

        $factory->create();
    }
}
