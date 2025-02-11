<?php

namespace App\Jobs;

use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class NeonDispatchHandler implements ShouldQueue
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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();
        $nativeCommandRequest = NativeCommandRequest::create([
            'guild_id' => $this->guildId,
            'channel_id' => $this->channelId,
            'discord_user_id' => $this->discordUserId,
            'message_content' => $this->messageContent,
            'command' => $this->command,
            //TODO: Add additional parameters here
            // 'additional_parameters' => $this->additionalParameters,
            'status' => 'pending',
            'executed_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Bus::dispatch(new $this->command['class']($nativeCommandRequest));
    }
}
