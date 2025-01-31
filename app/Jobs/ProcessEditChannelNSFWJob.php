<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelNSFWJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $commandChannelId, // The channel where the command was sent
        public string $targetChannelId, // The channel being edited
        public string $guildId,
        public bool $nsfw
    ) {}

    public function handle(): void
    {
        $url = config('services.discord.rest_api_url') . "/channels/{$this->targetChannelId}";
        $payload = ['nsfw' => $this->nsfw];

        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update NSFW setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->commandChannelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update NSFW setting.',
            ]);

            return;
        }

        // ✅ Now correctly displays true or false
        SendMessage::sendMessage($this->commandChannelId, [
            'is_embed' => true,
            'embed_title' => '✅ NSFW Setting Updated!',
            'embed_description' => '**NSFW:** 🔞 `' . ($this->nsfw ? 'Enabled' : 'Disabled') . '`',
            'embed_color' => 3447003,
        ]);
    }
}
