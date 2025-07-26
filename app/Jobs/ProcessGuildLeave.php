<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Guild;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessGuildLeave implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $guildId,
        private readonly string $guildName
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $guild = Guild::find($this->guildId);

        if (!$guild) {
            Log::warning('Received guild_delete for unknown guild', [
                'guild_id' => $this->guildId,
            ]);
            return;
        }

        $guild->update([
            'is_bot_member' => false,
            'bot_left_at' => now(),
            'last_bot_check_at' => now(),
        ]);

        // Park any active licenses
        $guild->licenses()->active()->each(function ($license) use ($guild) {
            $license->park();
            Log::info('Parked license due to bot leaving guild via websocket', [
                'license_id' => $license->id,
                'guild_id' => $guild->id,
            ]);
        });

        Log::info('Bot left guild via websocket', [
            'guild_id' => $this->guildId,
            'guild_name' => $this->guildName,
        ]);
    }
}
