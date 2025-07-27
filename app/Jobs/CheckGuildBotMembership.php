<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Guild;
use App\Services\Discord\DiscordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckGuildBotMembership implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Guild $guild
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DiscordService $discord): void
    {
        $wasInGuild = $this->guild->is_bot_member;
        $isInGuild = $discord->isBotInGuild($this->guild->id);

        // Update the guild's bot membership status
        $this->guild->update([
            'is_bot_member' => $isInGuild,
            'last_bot_check_at' => now(),
        ]);

        // Handle bot joining the guild
        if (! $wasInGuild && $isInGuild) {
            $this->guild->update(['bot_joined_at' => now()]);
            Log::info('Bot joined guild', ['guild_id' => $this->guild->id]);
        }

        // Handle bot leaving the guild
        if ($wasInGuild && ! $isInGuild) {
            $this->guild->update(['bot_left_at' => now()]);
            Log::info('Bot left guild', ['guild_id' => $this->guild->id]);

            // Optionally park licenses when bot leaves
            $this->guild->licenses()->active()->each(function ($license) {
                $license->park();
                Log::info('Parked license due to bot leaving guild', [
                    'license_id' => $license->id,
                    'guild_id' => $this->guild->id,
                ]);
            });
        }
    }
}
