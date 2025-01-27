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
        $commands = NeonCommand::query()
            ->select([
                'id',
                'command',
                'response',
            ])
            ->whereGuildId($this->guildId)
            ->whereIsEnabled(true)
            ->get()
            ->toArray();

        Cache::forever('guild-commands:' . $this->guildId, $commands);
    }
}
