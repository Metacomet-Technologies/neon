<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelNameJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $channelId,
        public string $guildId,
        public string $newName,
    ) {}

    public function handle(): void
    {
        $url = config('services.discord.rest_api_url') . "/channels/{$this->channelId}";
        $payload = ['name' => $this->newName];

        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to rename channel (ID: `{$this->channelId}`).");
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => "❌ Failed to rename channel."]);
            return;
        }

        SendMessage::sendMessage($this->channelId, ['is_embed' => true, 'embed_title' => '✅ Channel Renamed!', 'embed_description' => "**New Name:** #{$this->newName}", 'embed_color' => 3447003]);
    }
}
