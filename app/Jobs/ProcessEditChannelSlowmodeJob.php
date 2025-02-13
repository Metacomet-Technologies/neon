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

final class ProcessEditChannelSlowmodeJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public ?string $targetChannelId = null;
    public ?int $slowmodeSetting = null;

    public array $slowmodeRange = [0, 21600]; // 0 - 6 hours

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the message
        [$this->targetChannelId, $this->slowmodeSetting] = $this->parseMessage($this->messageContent);

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to edit channels in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels.',
                statusCode: 403,
            );

            return;
        }

        // ✅ If the command was used without parameters, send the help message
        if (! $this->targetChannelId || is_null($this->slowmodeSetting)) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Ensure slowmode setting is within Discord's allowed range (0-21600 seconds)
        if ($this->slowmodeSetting < $this->slowmodeRange[0] || $this->slowmodeSetting > $this->slowmodeRange[1]) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Slowmode must be between {$this->slowmodeRange[0]} and {$this->slowmodeRange[1]} seconds (6 hours).",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid slowmode setting provided.',
                statusCode: 400,
            );

            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['rate_limit_per_user' => $this->slowmodeSetting];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update slowmode setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update slowmode setting.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to update auto-hide setting.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Slowmode Updated!',
            'embed_description' => "**Slowmode Duration:** {$this->slowmodeSetting} seconds",
            'embed_color' => 3447003,
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Normalize curly quotes to straight quotes for mobile compatibility
        $message = str_replace(['“', '”'], '"', $message);

        // Use regex to parse the command properly
        preg_match('/^!edit-channel-slowmode\s+(<#\d{17,19}>|\d{17,19})\s+(\d+)$/', $message, $matches);

        // Validate if both channel and slowmode duration were provided
        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Ensure we return null values explicitly
        }

        $channelIdentifier = trim($matches[1]); // Extracted channel mention or ID
        $slowmodeSetting = (int) trim($matches[2]); // Convert to integer

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1]; // Extract just the ID
        }

        return [$channelIdentifier, $slowmodeSetting];
    }
}
