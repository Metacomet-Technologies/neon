<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Helpers\DiscordChannelValidator;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Discord\Parts\Channel\Channel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessNewChannelJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public array $channelTypes = ['text', 'voice'];

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
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
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels',
                statusCode: 403,
            );

            return;
        }

        // 2️⃣ Parse the command properly
        preg_match('/^!new-channel\s+(\S+)\s+(\S+)(?:\s+(\d+))?(?:\s+(.+))?$/', $this->messageContent, $matches);

        if (! isset($matches[1], $matches[2])) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel name provided.',
                statusCode: 400,
            );

            return;
        }

        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $validationResult['message'],
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel name provided.',
                statusCode: 400,
            );

            return;
        }

        // 4️⃣ Validate the channel type
        if (! in_array($channelType, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel type. Please use "text" or "voice".',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No channel type provided.',
                statusCode: 400,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to create channel.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

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
        $this->updateNativeCommandRequestComplete();
    }
}
