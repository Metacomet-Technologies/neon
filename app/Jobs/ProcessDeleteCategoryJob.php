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

final class ProcessDeleteCategoryJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'delete-category',
    // 'description' => 'Deletes a category.',
    // 'class' => \App\Jobs\ProcessDeleteCategoryJob::class,
    // 'usage' => 'Usage: !delete-category <category-id>',
    // 'example' => 'Example: !delete-category 123456789012345678',
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
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'delete-category')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');
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
                'response' => '❌ You do not have permission to manage categories in this server.',
            ]);

            return;
        }
        // 1️⃣ Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        $categoryId = $parts[1];

        // Ensure the provided category ID is numeric
        if (! is_numeric($categoryId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid category ID. Please provide a valid numeric ID.',
            ]);

            return;
        }

        // 2️⃣ Ensure the user has permission to delete categories
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to delete categories.',
            ]);

            return;
        }

        // 3️⃣ Fetch all channels to verify the category exists and is a category
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

        // 4️⃣ Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ No category found with ID `{$categoryId}`.",
            ]);

            return;
        }

        // 5️⃣ Check if category has child channels
        $childChannels = $channels->filter(fn ($c) => $c['parent_id'] === $categoryId);

        if ($childChannels->isNotEmpty()) {
            $channelList = $childChannels->map(fn ($c) => "`{$c['name']}` (ID: `{$c['id']}`)")->implode("\n");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Category ID `{$categoryId}` contains channels:\n{$channelList}\nPlease delete or move them first.",
            ]);

            return;
        }

        // 6️⃣ Construct the delete API request
        $deleteUrl = $this->baseUrl . "/channels/{$categoryId}";

        // 7️⃣ Make the delete request with retries
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

            return;
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
