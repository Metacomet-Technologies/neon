<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Helpers\DiscordChannelValidator;
use Discord\Parts\Channel\Channel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessNewChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !new-channel <channel-name> <channel-type> [category-id] [channel-topic]';
    public string $exampleMessage = 'Example: !new-channel test-channel text 123456789012345678 "A fun chat for everyone!"';

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

        // 2️⃣ Parse the command properly
        preg_match('/^!new-channel\s+(\S+)\s+(\S+)(?:\s+(\d+))?(?:\s+(.+))?$/', $this->message, $matches);

        if (! isset($matches[1], $matches[2])) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        $channelName = $matches[1];
        $channelType = $matches[2];
        $categoryId = isset($matches[3]) ? $matches[3] : null;
        $channelTopic = isset($matches[4]) ? trim($matches[4], '"') : null; // Remove extra quotes if used

        // 3️⃣ Validate the channel name
        if (in_array($channelName, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel name. Please use a different name.',
            ]);

            return;
        }

        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $validationResult['message'],
            ]);

            return;
        }

        // 4️⃣ Validate the channel type
        if (! in_array($channelType, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel type. Please use "text" or "voice".',
            ]);

            return;
        }

        // 5️⃣ Construct the API request payload
        $payload = [
            'name' => $channelName,
            'type' => $channelType === 'text' ? Channel::TYPE_GUILD_TEXT : Channel::TYPE_GUILD_VOICE,
        ];

        if ($categoryId) {
            $payload['parent_id'] = $categoryId;
        }

        if ($channelTopic) {
            $payload['topic'] = $channelTopic;
        }

        $url = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        // 6️⃣ Send request to create the channel
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
        $categoryMessage = $categoryId ? "**Category:** 📂 `<#{$categoryId}>`" : '**No category assigned**';
        $topicMessage = $channelTopic ? "**Topic:** 📝 `{$channelTopic}`" : '**No topic set**';

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Created!',
            'embed_description' => "**Channel Name:** #{$channelName}\n**Type:** " . ($channelType === 'text' ? '💬 Text' : '🔊 Voice') . "\n{$categoryMessage}\n{$topicMessage}",
            'embed_color' => 3447003, // Blue embed
        ]);
    }
}
