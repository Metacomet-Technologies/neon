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

final class ProcessAssignChannelJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;


            public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
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

            $this->nativeCommandRequest->update([
                'status' => 'unauthorized',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'User does not have permission to manage channels.',
                    'status_code' => 403,
                ],
            ]);

            return;
        }

        // Parse the command message
        [$channelInput, $categoryInput] = $this->parseMessage($this->messageContent);

        if (! $channelInput || ! $categoryInput) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->command['usage']}\n{$this->command['example']}",
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Missing required IDs.',
                    'details' => 'Required Ids not found in the message.',
                    'status_code' => 400,
                ],
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

            $this->nativeCommandRequest->update([
                'status' => 'discord-api-error',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to fetch channels from the server.',
                    'status_code' => $channelsResponse->status(),
                    'response' => $channelsResponse->json(),
                ],
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

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Channel not found.',
                    'details' => "Channel '{$channelInput}' not found in the server.",
                    'status_code' => 404,
                ],
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

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Category not found.',
                    'details' => "Category '{$categoryInput}' not found in the server.",
                    'status_code' => 404,
                ],
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

            $this->nativeCommandRequest->update([
                'status' => 'discord-api-error',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to move channel to category.',
                    'status_code' => $updateResponse->status(),
                    'response' => $updateResponse->json(),
                ],
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

        // 6️⃣ Update the status of the command request
        $this->nativeCommandRequest->update([
            'status' => 'executed',
            'executed_at' => now(),
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
