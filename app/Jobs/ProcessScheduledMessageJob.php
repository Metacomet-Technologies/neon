<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

//TODO: check permissions for elevation. this may not work anymore. not updated with processbasejob

class ProcessScheduledMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $channelId,
        public string $messageContent
    ) {}

    public function handle()
    {
        $url = config('services.discord.rest_api_url') . "/channels/{$this->channelId}/messages";

        $response = Http::withToken(config('discord.token'), 'Bot')->post($url, [
            'content' => $this->messageContent,
            'tts' => false,
        ]);

        if ($response->failed()) {
            Log::error('Failed to send scheduled message', [
                'channel_id' => $this->channelId,
                'message' => $this->messageContent,
                'response' => $response->json(),
            ]);
        } else {
            Log::info('Sent scheduled message', [
                'channel_id' => $this->channelId,
                'message' => $this->messageContent,
            ]);
        }
    }
}
