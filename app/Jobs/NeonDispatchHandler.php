<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

final class NeonDispatchHandler implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
        public array $command,
        public string $commandSlug,
        public array $parameters = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Dispatch the job with the new constructor pattern
        Bus::dispatch(new $this->command['class'](
            $this->discordUserId,
            $this->channelId,
            $this->guildId,
            $this->messageContent,
            $this->command,
            $this->commandSlug,
            $this->parameters
        ));
    }
}
