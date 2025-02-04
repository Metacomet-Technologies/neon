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

final class ProcessAssignChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'assign-channel',
    // 'description' => 'Assigns a channel to a category.',
    // 'class' => \App\Jobs\ProcessAssignChannelJob::class,
    // 'usage' => 'Usage: !assign-channel <channel-id|channel-name> <category-id|category-name>',
    // 'example' => 'Example: !assign-channel 123456789012345678 987654321098765432',
    // 'is_active' => true,

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

        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'assign-channel')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage channels in this server.',
            ]);

            return;
        }

        // Parse the command message
        [$channelInput, $categoryInput] = $this->parseMessage($this->messageContent);

        if (! $channelInput || ! $categoryInput) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // 2️⃣ Fetch all channels in the guild
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        $channelsResponse = retry(3, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, 200);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve channels from the server.',
            ]);

            return;
        }

        $channels = collect($channelsResponse->json());

        // 3️⃣ Find the target channel
        $channel = $channels->first(fn ($c) => $c['id'] === $channelInput || strcasecmp($c['name'], $channelInput) === 0);

        if (! $channel) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Channel '{$channelInput}' not found.",
            ]);

            return;
        }

        $channelId = $channel['id'];

        // 4️⃣ Find the target category
        // TODO: Implement logic to find the category without requiring a raw category ID.
        $category = $channels->first(fn ($c) => ($c['id'] === $categoryInput || strcasecmp($c['name'], $categoryInput) === 0) && $c['type'] === 4);

        if (! $category) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Category '{$categoryInput}' not found.",
            ]);

            return;
        }

        $categoryId = $category['id'];

        // 5️⃣ Move the channel to the new category
        $updateUrl = $this->baseUrl . "/channels/{$channelId}";

        $updateResponse = retry(3, function () use ($updateUrl, $categoryId) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->patch($updateUrl, ['parent_id' => $categoryId]);
        }, 200);

        if ($updateResponse->failed()) {
            Log::error("Failed to move channel '{$channelInput}' to category '{$categoryInput}' in guild {$this->guildId}", [
                'response' => $updateResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to move channel '{$channelInput}' to category '{$categoryInput}'.",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Moved!',
            'embed_description' => "**Channel Name:** #{$channel['name']} (ID: `{$channelId}`)\n**New Category:** 📂 {$category['name']} (ID: `{$categoryId}`)",
            'embed_color' => 3066993, // Green embed
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Remove invisible characters (zero-width spaces, control characters)
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message); // Removes control characters
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage)); // Normalize spaces

        // Use regex to extract the channel ID or name and category ID or name
        preg_match('/^!assign-channel\s+(<#?(\d{17,19})>|[\w-]+)\s+(<#?(\d{17,19})>|[\w-]+)$/iu', $cleanedMessage, $matches);

        if (! isset($matches[2], $matches[3])) { // ✅ Fix category index reference
            return [null, null]; // Invalid input
        }

        // Extract the channel ID
        $channelInput = trim($matches[2]); // This could be <#channelID> or channel name
        $categoryInput = trim($matches[3]); // This could be category ID or name

        // If channel mention format (<#channelID>), extract the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelInput, $channelMatches)) {
            $channelInput = $channelMatches[1]; // Extract numeric channel ID
        }

        return [$channelInput, $categoryInput];
    }
}
