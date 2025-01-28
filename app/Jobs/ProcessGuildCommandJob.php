<?php

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
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
        $url = $this->baseUrl . '/channels/' . $this->channelId . '/messages';
        $chatResponse = $this->command['response'];
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'content' => $this->setMessageOutput($chatResponse),
            ]);

        if ($apiResponse->failed()) {
            Log::error('Failed to send message to Discord', [
                'guild_id' => $this->guildId,
                'channel_id' => $this->channelId,
                'command' => $this->command,
                'message' => $this->message,
                'response' => $chatResponse,
                'api_response' => $apiResponse->json(),
            ]);
            // throw an exception so this can be retried
            throw new Exception('Failed to send message to Discord');
        }

        Log::info('Sent message to Discord', [
            'guild_id' => $this->guildId,
            'channel_id' => $this->channelId,
            'command' => $this->command,
            'message' => $this->message,
            'response' => $chatResponse,
            'api_response' => $apiResponse->json(),
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
