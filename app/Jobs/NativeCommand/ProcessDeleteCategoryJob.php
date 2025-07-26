<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteCategoryJob extends ProcessBaseJob
{
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
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage categories in this server.',
            ]);

            throw new Exception('User does not have permission to manage categories in this server.', 403);
        }
        //  Ensure the user has permission to delete categories
        // $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        // if ($adminCheck === 'failed') {
        //     SendMessage::sendMessage($this->channelId, [
        //         'is_embed' => false,
        //         'response' => '❌ You are not allowed to delete categories.',
        //     ]);
        //     throw new \Exception('Operation failed', 500);

        // }
        // Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            $this->sendUsageAndExample();

            throw new Exception('No category ID provided.', 400);
        }
        $categoryId = $parts[1];

        // Ensure the provided category ID is numeric
        if (! is_numeric($categoryId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid category ID. Please provide a valid numeric ID.',
            ]);
            throw new Exception('Operation failed', 500);
        }
        // Fetch all channels to verify the category exists and is a category
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        $apiResponse = retry(3, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to fetch channels for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve channels from the server.',
            ]);
            throw new Exception('Operation failed', 500);
        }
        $channels = collect($apiResponse->json());

        // 4️⃣ Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ No category found with ID `{$categoryId}`.",
            ]);
            throw new Exception('Category not found in guild.', 404);
        }
        // 5️⃣ Check if category has child channels
        $childChannels = $channels->filter(fn ($c) => $c['parent_id'] === $categoryId);

        if ($childChannels->isNotEmpty()) {
            $channelList = $childChannels->map(fn ($c) => "`{$c['name']}` (ID: `{$c['id']}`)")->implode("\n");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Category ID `{$categoryId}` contains channels:\n{$channelList}\nPlease delete or move them first.",
            ]);
            throw new Exception('Category contained child channels.', 400);
        }
        // Construct the delete API request
        $deleteUrl = $this->baseUrl . "/channels/{$categoryId}";

        // Make the delete request with retries
        $deleteResponse = retry(3, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
        }, 200);

        if ($deleteResponse->failed()) {
            Log::error("Failed to delete category '{$categoryId}' in guild {$this->guildId}", [
                'response' => $deleteResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete category (ID: `{$categoryId}`).",
            ]);
            throw new Exception('Operation failed', 500);
        }
        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Category Deleted!',
            'embed_description' => "**Category ID:** `{$categoryId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
    }
}
