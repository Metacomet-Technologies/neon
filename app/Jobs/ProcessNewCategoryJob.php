<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Models\NativeCommandRequest;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessNewCategoryJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $discordUserId;
    public string $channelId;
    public string $guildId;
    public string $messageContent;
    public array $command;

    // 'slug' => 'new-category',
    // 'description' => 'Creates a new category in the server.',
    // 'class' => \App\Jobs\ProcessNewCategoryJob::class,
    // 'usage' => 'Usage: !new-category <category-name>',
    // 'example' => 'Example: !new-category test-category',
    // 'is_active' => true,

    /**
     * Create a new job instance.
     */
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        // Fetch command details from the database
        $this->discordUserId = $nativeCommandRequest->discord_user_id;
        $this->channelId = $nativeCommandRequest->channel_id;
        $this->guildId = $nativeCommandRequest->guild_id;
        $this->messageContent = $nativeCommandRequest->message_content;
        $this->command = $nativeCommandRequest->command;

        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Validate that required IDs are provided.
        if (! $this->discordUserId || ! $this->channelId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->command['usage']}\n{$this->command['example']}",
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Missing required IDs.',
                    'details' => 'Required Ids lost in the process.',
                    'unicorn' => '🦄',
                    'status_code' => 500,
                ],
            ]);

            return;
        }

        // 1️⃣ Ensure the user has permission to create categories
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create categories.',
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'unauthorized',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'User does not have permission to create categories.',
                    'status_code' => 403,
                ],
            ]);

            return;
        }

        // 2️⃣ Parse the command: check for command with missing parameters
        $parts = explode(' ', $this->messageContent, 2);
        if (count($parts) < 2) {
            // Send the usage and example messages if no category name is provided.
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->command['usage'],
            ]);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->command['example'],
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'help-request',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'No category name provided.',
                    'status_code' => 418,
                ],
            ]);

            return;
        }

        // 3️⃣ Extract the category name
        $categoryName = trim($parts[1]);

        // 4️⃣ Create the category via Discord API
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $jsonPayload = json_encode([
            'name' => $categoryName,
            'type' => Channel::TYPE_GUILD_CATEGORY,
        ]);

        if ($jsonPayload === false) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create category.',
            ]);
            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to create category.',
                    'status_code' => 500,
                    'unicorn' => '🦄',
                ],
            ]);
            throw new Exception('Failed to create category.');
        }

        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody($jsonPayload, 'application/json')
            ->post($url);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create category.',
            ]);
            $this->nativeCommandRequest->update([
                'status' => 'discord-api-error',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to create category.',
                    'status_code' => $apiResponse->status(),
                    'response' => $apiResponse->json(),
                ],
            ]);
            throw new Exception('Failed to create category.');
        }

        // ✅ Send Embedded Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Category Created!',
            'embed_description' => "**Category Name:** 📂 {$categoryName}",
            'embed_color' => 3447003, // Blue embed
        ]);

        // 5️⃣ Update the status of the command request
        $this->nativeCommandRequest->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }
}
