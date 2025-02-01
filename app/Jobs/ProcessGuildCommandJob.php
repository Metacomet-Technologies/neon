<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessGuildCommandJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $command
     * @return void
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
        public array $command,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $result = SendMessage::sendMessage($this->channelId, $this->command);

        if ($result === 'failed') {
            Log::error('Failed to send message to Discord', [
                'channel_id' => $this->channelId,
                'message' => $this->messageContent,
            ]);
            throw new Exception('Failed to send message to Discord');
        }

        Log::info('Sent message to Discord', [
            'channel_id' => $this->channelId,
            'message' => $this->messageContent,
        ]);

    }
}
