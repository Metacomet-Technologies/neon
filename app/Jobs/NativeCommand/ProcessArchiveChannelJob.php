<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use App\Services\DiscordApiService;

final class ProcessArchiveChannelJob extends ProcessBaseJob
{
    public ?string $targetChannelId = null; // The actual Discord channel ID
    public ?bool $archiveStatus = null; // Archive (true) or unarchive (false)

    private int $retryDelay = 2000; // ✅ 2-second delay before retrying
    private int $maxRetries = 3;    // ✅ Max retries per request

    /**
     * Create a new job instance.
     */
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

        // Parse the message first
        [$this->targetChannelId, $this->archiveStatus] = $this->parseMessage($this->messageContent);
    }

    /**
     * Execute the specific command logic.
     */
    protected function executeCommand(): void
    {

        // ✅ If invalid input, send help message and **exit early**
        if (! $this->targetChannelId || is_null($this->archiveStatus)) {
            $this->sendUsageAndExample();

            return;
        }

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage channels in this server.',
            ]);

            throw new Exception('User does not have permission to manage channels.', 403);
        }
        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);

            throw new Exception('Invalid channel ID provided.', 400);
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['archived' => $this->archiveStatus];

        // Send the request to Discord API
        $discordService = app(DiscordApiService::class);
        $apiResponse = retry($this->maxRetries, function () use ($discordService, $payload) {
            return $discordService->patch("/channels/{$this->targetChannelId}", $payload);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update channel archive status.',
            ]);

            throw new Exception('Failed to update channel archive status: ' . $apiResponse->body(), $apiResponse->status());
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => $this->archiveStatus ? '📂 Channel Archived' : '📂 Channel Unarchived',
            'embed_description' => "✅ Channel <#{$this->targetChannelId}> has been " . ($this->archiveStatus ? 'archived' : 'unarchived') . '.',
            'embed_color' => $this->archiveStatus ? 15158332 : 3066993, // Red for archive, Green for unarchive
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to extract the channel ID or mention and archive/unarchive flag
        preg_match('/^!archive-channel\s+(<#?(\d{17,19})>)?\s*(true|false)?$/i', $message, $matches);

        if (! isset($matches[2], $matches[3])) {
            return [null, null]; // Invalid input
        }

        $channelIdentifier = trim($matches[2]);                    // Extracted numeric channel ID
        $archiveStatus = strtolower(trim($matches[3])) === 'true'; // Convert to boolean

        return [$channelIdentifier, $archiveStatus];
    }
}
