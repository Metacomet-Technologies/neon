<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelAutohideJob extends ProcessBaseJob
{
    public ?string $targetChannelId = null;
    public ?int $autoHideDuration = null;

    private array $allowedDurations = [60, 1440, 4320, 10080];

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
        // Parse the message
        [$this->targetChannelId, $this->autoHideDuration] = $this->parseMessage($this->messageContent);

        // ✅ If the command was used without parameters, send the help message
        if (! $this->targetChannelId || ! $this->autoHideDuration) {
            $this->sendUsageAndExample('Allowed values: `60, 1440, 4320, 10080` minutes.');

            throw new Exception('No user ID provided.', 400);
        }
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to edit channels in this server.',
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
        $payload = ['default_auto_archive_duration' => $this->autoHideDuration];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update auto-hide setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update auto-hide setting.',
            ]);
            throw new Exception('Operation failed', 500);
        }
        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Auto-Hide Updated!',
            'embed_description' => "**Auto-hide Duration:** ⏲️ `{$this->autoHideDuration} minutes`",
            'embed_color' => 3447003,
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to parse the command properly
        preg_match('/^!edit-channel-autohide\s+(<#\d{17,19}>|\d{17,19})\s+(\d+)$/', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }
        $channelIdentifier = trim($matches[1]);
        $autoHideDuration = (int) trim($matches[2]);

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1];
        }

        return [$channelIdentifier, $autoHideDuration];
    }
}
