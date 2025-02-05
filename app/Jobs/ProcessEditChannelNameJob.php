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

final class ProcessEditChannelNameJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'edit-channel-name',
    // 'description' => 'Edits a channel name.',
    // 'class' => \App\Jobs\ProcessEditChannelNameJob::class,
    // 'usage' => 'Usage: !edit-channel-name <channel-id> <new-name>',
    // 'example' => 'Example: !edit-channel-name 123456789012345678 new-channel-name',
    // 'is_active' => true,

    public string $baseUrl;
    public ?string $targetChannelId = null;
    public ?string $newName = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId, // The channel where the command was sent
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'edit-channel-name')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        [$this->targetChannelId, $this->newName] = $this->parseMessage($this->messageContent);
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // ✅ If the command was used without parameters, send the help message
        if (! $this->targetChannelId || ! $this->newName) {
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
        $payload = ['name' => $this->newName];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to rename channel (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to rename channel.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Renamed!',
            'embed_description' => "**New Name:** #{$this->newName}",
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
        preg_match('/^!edit-channel-name\s+(<#\d{17,19}>|\d{17,19})\s+(.+)$/', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }

        $channelIdentifier = trim($matches[1]);
        $newName = trim($matches[2]);

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1];
        }

        return [$channelIdentifier, $newName];
    }
}
