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

final class ProcessDeleteCategoryJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage categories in this server.',
            ]);

            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage categories in this server.',
                statusCode: 403,
            );

            return;
        }

        //  Ensure the user has permission to delete categories
        // $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        // if ($adminCheck === 'failed') {
        //     SendMessage::sendMessage($this->channelId, [
        //         'is_embed' => false,
        //         'response' => '❌ You are not allowed to delete categories.',
        //     ]);
        //     $this->updateNativeCommandRequestFailed(
        //         status: 'unauthorized',
        //         message: 'User does not have permission to delete categories.',
        //         statusCode: 403,
        //     );

        //     return;
        // }

        // Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No category ID provided.',
                statusCode: 400,
            );

            return;
        }

        $categoryId = $parts[1];

        // Ensure the provided category ID is numeric
        if (! is_numeric($categoryId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid category ID. Please provide a valid numeric ID.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid category ID format.',
                details: $categoryId,
                statusCode: 400,
            );

            return;
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
            $this->updateNativeCommandRequestFailed(
                status: 'discord-api-error',
                message: 'Failed to ban user.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

            return;
        }

        $channels = collect($apiResponse->json());

        // 4️⃣ Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ No category found with ID `{$categoryId}`.",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Category not found in guild.',
                statusCode: 404,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Category contained child channels.',
                statusCode: 400,
            );

            return;
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
            $this->updateNativeCommandRequestFailed(
                status: 'discord-api-error',
                message: 'Failed to delete category.',
                statusCode: $deleteResponse->status(),
                details: $deleteResponse->json(),
            );

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Category Deleted!',
            'embed_description' => "**Category ID:** `{$categoryId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
        $this->updateNativeCommandRequestComplete();
    }
}
