<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\DiscordRefreshToken;
use App\Models\User;
use Illuminate\Console\Command;

final class CheckDiscordTokens extends Command
{
    protected $signature = 'discord:check-tokens';

    protected $description = 'Check and refresh expired Discord tokens for all users';

    public function handle(): int
    {
        $this->info('Checking Discord tokens for all users...');

        $users = User::whereNotNull('access_token')
            ->whereNotNull('refresh_token')
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '<', now());
            })
            ->where('refresh_token_expires_at', '>', now())
            ->get();

        $refreshed = 0;
        $failed = 0;

        foreach ($users as $user) {
            $this->info("Checking user {$user->id} ({$user->email})");

            $refreshHelper = new DiscordRefreshToken($user);
            $newToken = $refreshHelper->refreshToken();

            if ($newToken) {
                $refreshed++;
                $this->info("✅ Refreshed token for user {$user->id}");
            } else {
                $failed++;
                $this->error("❌ Failed to refresh token for user {$user->id}");

                // Clear invalid tokens
                $user->update([
                    'access_token' => null,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'refresh_token_expires_at' => null,
                ]);
            }
        }

        $this->info("Token refresh complete: {$refreshed} refreshed, {$failed} failed");

        return 0;
    }
}
