<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NeonCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class CacheGuildCommandsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $guildId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'guild-commands:' . $this->guildId;

        $commands = NeonCommand::query()
            ->aciveGuildCommands($this->guildId)
            ->get()
            ->toArray();

        Cache::forever($key, $commands);
    }
}
