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
     */
    public function __construct(
        public string $guildId,
        public string $channelId,
        public array $command,
        public string $message,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $result = SendMessage::sendMessage($this->command['response'], $this->channelId);
        if ($result === 'failed') {
            Log::error('Failed to send message to Discord', [
                'channel_id' => $this->channelId,
                'message' => $this->message,
            ]);
            throw new Exception('Failed to send message to Discord');
        }

        Log::info('Sent message to Discord', [
            'channel_id' => $this->channelId,
            'message' => $this->message,
        ]);

    }

    /**
     * Set the message output based on the environment.
     */
    private function setMessageOutput(string $message): string
    {
        $environment = config('app.env');
        if ($environment === 'production') {
            return $message;
        }

        return '[' . $environment . '] ' . $message;
    }
}
