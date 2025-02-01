<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelSlowmodeJob implements ShouldQueue
{
    use Queueable;

    //TODO: Make the job implement this constructor to align with other jobs
    // /**
    //  * Create a new job instance.
    //  */
    // public function __construct(
    //     public string $discordUserId,
    //     public string $channelId,
    //     public string $guildId,
    //     public string $messageContent,
    // ) {
    //     $this->baseUrl = config('services.discord.rest_api_url');
    // }
    public function __construct(
        public string $channelId,
        public string $guildId,
        public int $slowmodeSeconds,
    ) {}

    public function handle(): void
    {
        if ($this->slowmodeSeconds < 0 || $this->slowmodeSeconds > 21600) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Invalid slow mode value. Must be between 0 and 21600 seconds.']);

            return;
        }

        $url = config('services.discord.rest_api_url') . "/channels/{$this->channelId}";
        $payload = ['rate_limit_per_user' => $this->slowmodeSeconds];

        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update slowmode (ID: `{$this->channelId}`).");
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Failed to update slow mode.']);

            return;
        }

        SendMessage::sendMessage($this->channelId, ['is_embed' => true, 'embed_title' => '✅ Slow Mode Updated!', 'embed_description' => "**Slowmode:** ⏳ `{$this->slowmodeSeconds} sec`", 'embed_color' => 3447003]);
    }
}
