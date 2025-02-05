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

final class ProcessEditChannelTopicJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'edit-channel-topic',
    // 'description' => 'Edits a channel topic.',
    // 'class' => \App\Jobs\ProcessEditChannelTopicJob::class,
    // 'usage' => 'Usage: !edit-channel-topic <channel-id> <new-topic>',
    // 'example' => 'Example: !edit-channel-topic 123456789012345678 New topic description',
    // 'is_active' => true,
    private string $baseUrl;
    private ?string $targetChannelId = null; // The actual Discord channel ID to edit
    private ?string $newTopic = null;        // The new channel topic

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'edit-channel-topic')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');

        // Normalize curly quotes to straight quotes for better parsing
        $normalizedMessage = str_replace(['“', '”'], '"', $this->messageContent);

        // Parse the message
        [$this->targetChannelId, $this->newTopic] = $this->parseMessage($normalizedMessage);
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Validate input: If no channel/topic provided, send help message
        if (! $this->targetChannelId || ! $this->newTopic) {
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

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['topic' => $this->newTopic];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update channel topic (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update channel topic.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Topic Updated!',
            'embed_description' => "**New Topic:** {$this->newTopic}",
            'embed_color' => 3447003,
        ]);
    }

    private function parseMessage(string $message): array
    {
        // Use regex to parse the command properly
        preg_match('/^!edit-channel-topic\s+(<#\d{17,19}>|\d{17,19})\s+(.+)$/', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }

        $channelIdentifier = $matches[1]; // Extracted channel mention or ID
        $newTopic = trim($matches[2]); // Extracted new topic

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1]; // Extract just the ID
        }

        return [$channelIdentifier, $newTopic];
    }
}
