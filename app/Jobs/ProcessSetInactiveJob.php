<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

//TODO: This job has logic for discovering voice channels based on name without mentioning them. This could be added to other jobs that interface with voice channels.

final class ProcessSetInactiveJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?string $targetChannelId = null; // The actual Discord voice channel ID
    private ?int $afkTimeout = null;         // Timeout duration in seconds

    /**
     * Allowed AFK timeout values (in seconds).
     */
    private array $allowedTimeouts = [60, 300, 900, 1800, 3600];

    /**
     * Create a new job instance.
     */
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Parse the message
        [$channelInput, $this->afkTimeout] = $this->parseMessage($this->messageContent);

        // 🚨 **Validation: Show help message if no arguments are provided**
        if ($channelInput === null || $this->afkTimeout === null) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No arguments provided.',
                statusCode: 400,
            );
        }

        // Ensure `$channelInput` is a string before passing it to `resolveVoiceChannelId()`
        $this->targetChannelId = $this->resolveVoiceChannelId((string) $channelInput);

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage channels in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels.',
                statusCode: 403,
            );

            return;
        }
        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use a valid voice channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/guilds/{$this->guildId}";
        $payload = [
            'afk_channel_id' => $this->targetChannelId,
            'afk_timeout' => $this->afkTimeout,
        ];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to set AFK channel (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to set inactive voice channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to set inactive voice channel.',
                details: $apiResponse->json(),
                statusCode: $apiResponse->status(),
            );

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🎧 Inactive Channel Set',
            'embed_description' => "**AFK Channel:** <#{$this->targetChannelId}>\n**Timeout:** ⏳ `{$this->afkTimeout} sec`",
            'embed_color' => 3447003,
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to extract the channel ID or name and timeout
        preg_match('/^!set-inactive\s+(\S+)\s+(\d+)$/', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Invalid input
        }

        $channelInput = trim($matches[1]); // Can be a name or ID
        $afkTimeout = (int) trim($matches[2]);

        return [$channelInput, $afkTimeout];
    }

    /**
     * Resolves a voice channel name to its ID.
     */
    private function resolveVoiceChannelId(string $input): ?string
    {
        // If input is already a valid channel ID, return it immediately
        if (preg_match('/^\d{17,19}$/', $input)) {
            return $input;
        }

        // Fetch all channels in the guild
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return null;
        }

        $channels = collect($response->json());

        // Normalize input for comparison
        $normalizedInput = strtolower(trim($input));

        // Find the matching voice channel
        $matchedChannel = $channels->first(function ($ch) use ($normalizedInput) {
            return $ch['type'] === 2 && strtolower($ch['name']) === $normalizedInput;
        });

        return $matchedChannel['id'] ?? null;
    }
}
