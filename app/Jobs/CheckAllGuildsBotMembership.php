<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\CheckBotMembership;
use App\Models\Guild;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckAllGuildsBotMembership implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $checker = new CheckBotMembership;

        // Get all guilds the bot is currently in
        $botGuilds = collect($checker->getBotGuilds())->pluck('id')->toArray();

        Log::info('Checking bot membership for all guilds', [
            'bot_guild_count' => count($botGuilds),
        ]);

        // Update guilds where bot is a member
        Guild::whereIn('id', $botGuilds)->each(function (Guild $guild) {
            if (! $guild->is_bot_member) {
                $guild->update([
                    'is_bot_member' => true,
                    'bot_joined_at' => now(),
                    'last_bot_check_at' => now(),
                ]);
                Log::info('Bot detected in guild', ['guild_id' => $guild->id]);
            } else {
                $guild->update(['last_bot_check_at' => now()]);
            }
        });

        // Update guilds where bot is not a member
        Guild::whereNotIn('id', $botGuilds)->where('is_bot_member', true)->each(function (Guild $guild) {
            $guild->update([
                'is_bot_member' => false,
                'bot_left_at' => now(),
                'last_bot_check_at' => now(),
            ]);

            Log::info('Bot no longer in guild', ['guild_id' => $guild->id]);

            // Park any active licenses
            $guild->licenses()->active()->each(function ($license) {
                $license->park();
                Log::info('Parked license due to bot leaving guild', [
                    'license_id' => $license->id,
                    'guild_id' => $guild->id,
                ]);
            });
        });

        // Check guilds that haven't been checked recently (stale checks)
        Guild::where('last_bot_check_at', '<', now()->subHours(24))
            ->orWhereNull('last_bot_check_at')
            ->each(function (Guild $guild) {
                CheckGuildBotMembership::dispatch($guild);
            });
    }
}
