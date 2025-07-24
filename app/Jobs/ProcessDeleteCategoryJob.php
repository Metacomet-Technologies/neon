<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
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
        $parts = explode(' ', $this->messageContent, 2);

        if (count($parts) < 2) {
            $this->sendUsageAndExample();

            throw new Exception('No category ID provided.', 400);
        }

        $categoryId = trim($parts[1]);

        // If it's not a numeric ID, try to resolve it as a category name
        if (! is_numeric($categoryId)) {
            $resolvedId = $this->resolveCategoryByName($categoryId);
            if (!$resolvedId) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Category '{$categoryId}' not found. Please provide a valid category name or numeric ID.",
                ]);
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Category not found.',
                    details: $categoryId,
                    statusCode: 404,
                );

                return;
            }
            $categoryId = $resolvedId;
        }        // Fetch all channels to verify the category exists and is a category
        // with circuit breaker for persistent API failures
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";
        $cacheKey = "discord_api_failure_{$this->guildId}";
        $failureCount = Cache::get($cacheKey, 0);

        if ($failureCount >= 3) {
            Log::warning("Circuit breaker: Too many API failures for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '⚠️ Discord API is experiencing issues. Operation paused to prevent rate limiting. Please try again in 2-3 minutes.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'circuit-breaker',
                message: 'Circuit breaker activated due to repeated API failures.',
                statusCode: 429,
            );
            return;
        }

        $apiResponse = retry(5, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(30)
                ->get($channelsUrl);
        }, [1000, 2000, 3000, 5000, 8000]); // Progressive delays

        if ($apiResponse->failed()) {
            // Increment failure counter
            Cache::put($cacheKey, $failureCount + 1, now()->addMinutes(5));

            Log::error("Failed to fetch channels for guild {$this->guildId}", [
                'status_code' => $apiResponse->status(),
                'response' => $apiResponse->json(),
                'url' => $channelsUrl,
                'failure_count' => $failureCount + 1
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Discord API is temporarily unavailable. Please try again in a few moments.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord-api-error',
                message: 'Failed to fetch guild channels.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

            return;
        }
        $channels = collect($apiResponse->json());

        // Reset circuit breaker on successful API call
        Cache::forget($cacheKey);

        // 4️⃣ Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ No category found with ID `{$categoryId}`.",
            ]);
            throw new Exception('Category not found in guild.', 404);
        }

        // 5️⃣ Check if category has child channels - offer to delete them automatically
        $childChannels = $channels->filter(fn ($c) => $c['parent_id'] === $categoryId);

        if ($childChannels->isNotEmpty()) {
            // Auto-delete child channels first
            $deletedChannels = [];
            $failedChannels = [];

            foreach ($childChannels as $channel) {
                sleep(1); // Rate limiting protection

                $deleteChannelUrl = $this->baseUrl . "/channels/{$channel['id']}";

                $channelDeleteResponse = retry(3, function () use ($deleteChannelUrl) {
                    return Http::withToken(config('discord.token'), 'Bot')
                        ->timeout(30)
                        ->delete($deleteChannelUrl);
                }, [1000, 2000, 3000]);

                if ($channelDeleteResponse->successful()) {
                    $deletedChannels[] = $channel['name'];
                } else {
                    $failedChannels[] = $channel['name'];
                    Log::warning("Failed to auto-delete channel {$channel['name']} (ID: {$channel['id']})", [
                        'status_code' => $channelDeleteResponse->status(),
                        'response' => $channelDeleteResponse->json()
                    ]);
                }
            }

            if (!empty($failedChannels)) {
                $failedList = implode(', ', $failedChannels);
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Cannot delete category `{$categoryId}`. Failed to auto-delete these channels: {$failedList}",
                ]);
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Failed to auto-delete child channels.',
                    statusCode: 400,
                );
                return;
            }

            if (!empty($deletedChannels)) {
                $count = count($deletedChannels);
                $deletedList = implode(', ', $deletedChannels);
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "🗑️ Auto-deleted {$count} channels: {$deletedList}",
                ]);
            }
        }

        // Add rate limiting delay before category deletion
        sleep(2);

        // Construct the delete API request
        $deleteUrl = $this->baseUrl . "/channels/{$categoryId}";

        // Make the delete request with enhanced retries
        $deleteResponse = retry(5, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(30)
                ->delete($deleteUrl);
        }, [1000, 2000, 3000, 5000, 8000]);

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

    /**
     * Resolve category name to Discord category ID
     */
    private function resolveCategoryByName(string $categoryName): ?string
    {
        // Fetch all channels/categories in the guild
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";

        $channelsResponse = retry(5, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(30)
                ->get($channelsUrl);
        }, [1000, 2000, 3000, 5000, 8000]);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild {$this->guildId}");
            return null;
        }

        $channels = collect($channelsResponse->json());

        // Find category by name (case insensitive) - type 4 is category
        $category = $channels->first(function ($channel) use ($categoryName) {
            return $channel['type'] === 4 && strcasecmp($channel['name'], $categoryName) === 0;
        });

        return $category ? $category['id'] : null;
    }
}
