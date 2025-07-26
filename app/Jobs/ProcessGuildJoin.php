<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Guild;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessGuildJoin implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $guildId,
        private readonly string $guildName,
        private readonly ?string $guildIcon = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $guild = Guild::updateOrCreate(
            ['id' => $this->guildId],
            [
                'name' => $this->guildName,
                'icon' => $this->guildIcon,
                'is_bot_member' => true,
                'bot_joined_at' => now(),
                'last_bot_check_at' => now(),
            ]
        );

        Log::info('Bot joined guild via websocket', [
            'guild_id' => $this->guildId,
            'guild_name' => $guild->name,
        ]);
    }
}
