<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelSlowmodeJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'edit-channel-slowmode',
    // 'description' => 'Edits a channel to have slowmode.',
    // 'class' => \App\Jobs\ProcessEditChannelSlowmodeJob::class,
    // 'usage' => 'Usage: !edit-channel-slowmode <channel-id> <seconds [0 - 21600]>',
    // 'example' => 'Example: !edit-channel-slowmode 123456789012345678 10',
    // 'is_active' => true,

    public ?string $targetChannelId = null;
    public ?int $slowmodeSetting = null;

    /**
     * The minimum and maximum allowed slowmode durations in seconds.
     */
    public array $slowmodeRange = [0, 21600]; // 0 - 6 hours

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'edit-channel-slowmode')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        [$this->targetChannelId, $this->slowmodeSetting] = $this->parseMessage($this->messageContent);
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // ✅ If the command was used without parameters, send the help message
        if (! $this->targetChannelId || is_null($this->slowmodeSetting)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to edit channels in this server.',
            ]);

            return;
        }

        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);

            return;
        }

        // Ensure slowmode setting is within Discord's allowed range (0-21600 seconds)
        if ($this->slowmodeSetting < $this->slowmodeRange[0] || $this->slowmodeSetting > $this->slowmodeRange[1]) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Slowmode must be between {$this->slowmodeRange[0]} and {$this->slowmodeRange[1]} seconds (6 hours).",
            ]);

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

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Slowmode Updated!',
            'embed_description' => "**Slowmode Duration:** {$this->slowmodeSetting} seconds",
            'embed_color' => 3447003,
        ]);
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
