<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessArchiveChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $discordUserId;
    public string $channelId; // The channel where the command was sent
    public string $guildId;
    public string $messageContent;
    public array $command;
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'archive-channel',
    // 'description' => 'Archives or unarchives a channel.',
    // 'class' => \App\Jobs\ProcessArchiveChannelJob::class,
    // 'usage' => 'Usage: !archive-channel <channel-id> <true|false>',
    // 'example' => 'Example: !archive-channel 123456789012345678 true',
    // 'is_active' => false,

    public ?string $targetChannelId = null; // The actual Discord channel ID
    public ?bool $archiveStatus = null; // Archive (true) or unarchive (false)

    private int $retryDelay = 2000; // ✅ 2-second delay before retrying
    private int $maxRetries = 3;    // ✅ Max retries per request

    /**
     * Create a new job instance.
     */
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        // Fetch command details from the database
        $this->discordUserId = $nativeCommandRequest->discord_user_id;
        $this->channelId = $nativeCommandRequest->channel_id;
        $this->guildId = $nativeCommandRequest->guild_id;
        $this->messageContent = $nativeCommandRequest->message_content;
        $this->command = $nativeCommandRequest->command;
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message first
        [$this->targetChannelId, $this->archiveStatus] = $this->parseMessage($this->messageContent);

    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {

        // ✅ If invalid input, send help message and **exit early**
        if (! $this->targetChannelId || is_null($this->archiveStatus)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->command['usage']}\n{$this->command['example']}",
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Missing required IDs.',
                    'details' => 'Required Ids lost in the process.',
                    'status_code' => 500,
                ],
            ]);

            return;
        }

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage channels in this server.',
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'unauthorized',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'User does not have permission to manage channels.',
                    'status_code' => 403,
                ],
            ]);

            return;
        }
        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Invalid channel ID provided.',
                    'details' => 'Channel ID must be a valid Discord channel ID.',
                    'status_code' => 400,
                ],
            ]);

            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['archived' => $this->archiveStatus];

        // Send the request to Discord API
        $apiResponse = retry($this->maxRetries, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update channel archive status.',
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'discord-api-error',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to update channel archive status.',
                    'details' => $apiResponse->json(),
                    'status_code' => $apiResponse->status(),
                ],
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => $this->archiveStatus ? '📂 Channel Archived' : '📂 Channel Unarchived',
            'embed_description' => "✅ Channel <#{$this->targetChannelId}> has been " . ($this->archiveStatus ? 'archived' : 'unarchived') . '.',
            'embed_color' => $this->archiveStatus ? 15158332 : 3066993, // Red for archive, Green for unarchive
        ]);

        // 5️⃣ Update the status of the command request
        $this->nativeCommandRequest->update([
            'status' => 'executed',
            'executed_at' => now(),
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
