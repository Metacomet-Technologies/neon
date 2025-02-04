<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDisplayBoostJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !display-boost <true|false>';
    public string $exampleMessage = 'Example: !display-boost true';

    private string $baseUrl;
    private ?bool $displayBoost = null; // Whether to enable or disable the boost bar

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        $this->displayBoost = $this->parseMessage($this->messageContent);

        // ❌ If parsing failed, send an error message and stop execution
        if ($this->displayBoost === null) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input. Use `true` or `false`.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            // Stop job execution
            throw new Exception('Invalid input for !display-boost. Expected true or false.');
        }
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage boost display in this server.',
            ]);

            return;
        }
        // Build API request
        $url = "{$this->baseUrl}/guilds/{$this->guildId}";
        $payload = ['premium_progress_bar_enabled' => $this->displayBoost];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update boost progress bar for guild (ID: `{$this->guildId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update boost progress bar.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🚀 Boost Progress Bar Updated',
            'embed_description' => $this->displayBoost
                ? '✅ Server Boost Progress Bar is now **enabled**.'
                : '❌ Server Boost Progress Bar is now **disabled**.',
            'embed_color' => $this->displayBoost ? 3447003 : 15158332, // Blue for enabled, Red for disabled
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): ?bool
    {
        // Use regex to extract true/false parameter
        preg_match('/^!display-boost\s+(true|false)$/i', $message, $matches);

        if (! isset($matches[1])) {
            return null;
        }

        return strtolower(trim($matches[1])) === 'true';
    }
}
