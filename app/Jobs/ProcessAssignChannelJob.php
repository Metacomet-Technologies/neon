<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessAssignChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !assign-channel <channel-id|channel-name> <category-id|category-name>';
    public string $exampleMessage = 'Example: !assign-channel 123456789012345678 987654321098765432';

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
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Parse the command
        $parts = explode(' ', $this->messageContent, 3);

        if (count($parts) < 3) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->usageMessage,
            ]);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->exampleMessage,
            ]);

            return;
        }

        $channelInput = $parts[1]; // Can be an ID or name
        $categoryInput = $parts[2]; // Can be an ID or name

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

        // 3️⃣ Find the target channel by ID first (preferred)
        $channel = $channels->first(fn ($c) => $c['id'] === $channelInput);

        // If no ID match, try to match by name
        if (! $channel) {
            $matchingChannels = $channels->filter(fn ($c) => strcasecmp($c['name'], $channelInput) === 0);

            if ($matchingChannels->count() > 1) {
                $matchesList = $matchingChannels->map(fn ($c) => "**{$c['name']}** (ID: `{$c['id']}`)")->implode("\n");

                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Multiple channels named '{$channelInput}' found. Please use an ID instead:\n{$matchesList}",
                ]);

                return;
            }

            $channel = $matchingChannels->first();
        }

        if (! $channel) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Channel '{$channelInput}' not found.",
            ]);

            return;
        }

        $channelId = $channel['id'];

        // 4️⃣ Find the category by ID first (preferred)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryInput && $c['type'] === 4); // Type 4 = Category

        // If no ID match, try to match by name
        if (! $category) {
            $matchingCategories = $channels->filter(fn ($c) => strcasecmp($c['name'], $categoryInput) === 0 && $c['type'] === 4);

            if ($matchingCategories->count() > 1) {
                $matchesList = $matchingCategories->map(fn ($c) => "**{$c['name']}** (ID: `{$c['id']}`)")->implode("\n");

                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Multiple categories named '{$categoryInput}' found. Please use an ID instead:\n{$matchesList}",
                ]);

                return;
            }

            $category = $matchingCategories->first();
        }

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
}
