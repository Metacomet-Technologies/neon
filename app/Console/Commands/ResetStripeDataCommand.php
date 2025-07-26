<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ResetStripeDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:reset {--user= : Specific user ID to reset} {--all : Reset all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Stripe customer data for development/testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->resetAllUsers();
        }

        if ($userId = $this->option('user')) {
            return $this->resetUser((int) $userId);
        }

        $this->error('Please specify --user=ID or --all flag');

        return self::FAILURE;
    }

    /**
     * Reset Stripe data for all users.
     */
    private function resetAllUsers(): int
    {
        $users = User::whereNotNull('stripe_id')->get();

        if ($users->isEmpty()) {
            $this->info('No users with Stripe data found.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Reset Stripe data for {$users->count()} users?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $this->resetUserStripeData($user);
            $this->info("Reset Stripe data for user: {$user->email}");
        }

        // Also clear subscriptions and subscription items
        DB::table('subscriptions')->truncate();
        DB::table('subscription_items')->truncate();

        $this->info('✅ All Stripe data has been reset.');

        return self::SUCCESS;
    }

    /**
     * Reset Stripe data for a specific user.
     */
    private function resetUser(int $userId): int
    {
        $user = User::find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return self::FAILURE;
        }

        if (! $user->stripe_id) {
            $this->info("User {$user->email} has no Stripe data to reset.");

            return self::SUCCESS;
        }

        $this->resetUserStripeData($user);

        // Clear user's subscriptions
        DB::table('subscriptions')->where('user_id', $userId)->delete();
        DB::table('subscription_items')->whereIn('subscription_id', function ($query) use ($userId) {
            $query->select('id')->from('subscriptions')->where('user_id', $userId);
        })->delete();

        $this->info("✅ Reset Stripe data for user: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * Reset Stripe-related fields for a user.
     */
    private function resetUserStripeData(User $user): void
    {
        $user->update([
            'stripe_id' => null,
            'pm_type' => null,
            'pm_last_four' => null,
            'trial_ends_at' => null,
        ]);
    }
}
