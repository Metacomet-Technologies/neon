<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\DiscordChannelValidator;
use App\Jobs\NativeCommand\ProcessBaseJob;
use Discord\Parts\Channel\Channel;
use Exception;

final class ProcessNewChannelJob extends ProcessBaseJob
{
    private array $channelTypes = ['text', 'voice'];

    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse new channel command with complex parameters
        [$channelName, $channelType, $categoryId, $channelTopic] = $this->parseNewChannelCommand($this->messageContent);

        // 2️⃣ Parse the command properly to handle both category ID and name
        preg_match('/^!new-channel\s+(\S+)\s+(\S+)(?:\s+(\S+))?(?:\s+(.+))?$/', $this->messageContent, $matches);

        if (! isset($matches[1], $matches[2])) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $channelName = $matches[1];
        $channelType = $matches[2];
        $categoryIdentifier = isset($matches[3]) ? $matches[3] : null; // Could be ID or name
        $channelTopic = isset($matches[4]) ? trim($matches[4], '"') : null; // Remove extra quotes if used

        // 3️⃣ Validate the channel name
        if (in_array($channelName, $this->channelTypes)) {
            $this->sendErrorMessage('Invalid channel name. Please use a different name.');
            throw new Exception('Invalid channel name provided.', 400);
        }

        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            $this->sendErrorMessage($validationResult['message']);
            throw new Exception('Invalid channel name provided.', 400);
        }

        // 4. Validate channel type
        if (! in_array($channelType, $this->channelTypes)) {
            $this->sendErrorMessage('Invalid channel type. Please use "text" or "voice".');
            throw new Exception('Invalid channel type provided.', 400);
        }

        // 5️⃣ Resolve category ID if category identifier is provided
        $categoryId = null;
        if ($categoryIdentifier) {
            $categoryId = $this->resolveCategoryId($categoryIdentifier);
            if (!$categoryId) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Category '{$categoryIdentifier}' not found.",
                ]);
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Category not found.',
                    statusCode: 404,
                );
                return;
            }
        }

        // 6️⃣ Construct the API request payload
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

        $createdChannel = $this->discord->createChannel($this->guildId, $payload);

        // 7️⃣ Send request to create the channel
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

        // 6. Send confirmation
        $details = [];
        $details[] = '**Type:** ' . ($channelType === 'text' ? '💬 Text' : '🔊 Voice');
        if ($categoryId) {
            $details[] = "**Category:** 📂 <#{$categoryId}>";
        }
        if ($channelTopic) {
            $details[] = "**Topic:** 📝 {$channelTopic}";
        }

        $this->sendSuccessMessage(
            'Channel Created!',
            "**Channel:** #{$channelName}\n" . implode("\n", $details)
        );
    }

    /**
     * Parse new channel command with complex parameters.
     */
    private function parseNewChannelCommand(string $messageContent): array
    {
        // Pattern: !new-channel <name> <type> [categoryId] ["topic"]
        preg_match('/^!new-channel\s+(\S+)\s+(\S+)(?:\s+(\d+))?(?:\s+(.+))?$/', $messageContent, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null, null, null];
        }

        $channelName = $matches[1];
        $channelType = $matches[2];
        $categoryId = isset($matches[3]) ? $matches[3] : null;
        $channelTopic = isset($matches[4]) ? trim($matches[4], '"') : null;

        return [$channelName, $channelType, $categoryId, $channelTopic];
    }

    /**
     * Resolve category identifier to Discord category ID
     * Supports both numeric IDs and category names
     */
    private function resolveCategoryId(string $categoryIdentifier): ?string
    {
        // If it's already a numeric Discord ID, return it
        if (preg_match('/^\d{17,19}$/', $categoryIdentifier)) {
            return $categoryIdentifier;
        }

        // Fetch all channels/categories in the guild
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        $channelsResponse = retry(3, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, 200);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild {$this->guildId}");
            return null;
        }

        $channels = collect($channelsResponse->json());

        // Find category by name (case insensitive) - type 4 is category
        $category = $channels->first(function ($channel) use ($categoryIdentifier) {
            return $channel['type'] === 4 && strcasecmp($channel['name'], $categoryIdentifier) === 0;
        });

        return $category ? $category['id'] : null;
    }
}
