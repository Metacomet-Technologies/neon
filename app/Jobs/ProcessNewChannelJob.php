<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Helpers\DiscordChannelValidator;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessNewChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !new-channel <channel-name> <channel-type> [category-id]';
    public string $exampleMessage = 'Example: !new-channel test-channel text 123456789012345678';

    /**
     * The types of channels that can be created.
     *
     * @var array<string>
     */
    public array $channelTypes = ['text', 'voice'];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $message,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Ensure the user has permission to create channels
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create channels.',
            ]);
            return;
        }

        // 2️⃣ Parse the command
        $parts = explode(' ', $this->message);

        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);
            return;
        }

        // 3️⃣ Extract the channel name
        $channelName = $parts[1];

        // If the channel name is one of the channel types, it's most likely a mistake
        if (in_array($channelName, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Invalid channel name. Please use a different name.']);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);
            return;
        }

        // 4️⃣ Validate the channel name
        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $validationResult['message']]);
            return;
        }

        // 5️⃣ Extract the channel type (default: text)
        $channelType = $parts[2] ?? 'text';

        if (! in_array($channelType, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Invalid channel type. Please use "text" or "voice".']);
            return;
        }

        // 6️⃣ Extract category ID (optional)
        $categoryId = $parts[3] ?? null;

        if ($categoryId && !is_numeric($categoryId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid category ID. Please provide a valid numeric ID.',
            ]);
            return;
        }

        // 7️⃣ Construct the API request payload
        $payload = [
            'name' => $channelName,
            'type' => $channelType === 'text' ? Channel::TYPE_GUILD_TEXT : Channel::TYPE_GUILD_VOICE,
        ];

        if ($categoryId) {
            $payload['parent_id'] = $categoryId;
        }

        $url = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        // 8️⃣ Send request to create the channel
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->post($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to create channel '{$channelName}' in guild {$this->guildId}", [
                'response' => $apiResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to create channel '{$channelName}'.",
            ]);
            return;
        }

        // ✅ Success! Send confirmation message
        $categoryMessage = $categoryId ? "**Category:** 📂 `<#{$categoryId}>`" : "**No category assigned**";

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Created!',
            'embed_description' => "**Channel Name:** #{$channelName}\n**Type:** " . ($channelType === 'text' ? '💬 Text' : '🔊 Voice') . "\n{$categoryMessage}",
            'embed_color' => 3447003, // Blue embed
        ]);
    }
}
