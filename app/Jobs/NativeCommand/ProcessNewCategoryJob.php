<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\DiscordService;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessNewCategoryJob extends ProcessBaseJob
{
    use Queueable;

    public string $baseUrl;
    public string $discordUserId;
    public string $channelId;
    public string $guildId;
    public string $messageContent;
    public array $command;

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

    /**
     * Execute the job.
     */
    protected function executeCommand(): void
    {
        // Validate that required IDs are provided.
        if (! $this->discordUserId || ! $this->channelId) {
            $discord = app(DiscordService::class);
            $discord->channel($this->channelId)->send("{$this->command['usage']}\n{$this->command['example']}");

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
        $discord = app(DiscordService::class);
        if (! $discord->guild($this->guildId)->member($this->discordUserId)->canManageChannels()) {
            $discord->channel($this->channelId)->send('❌ You are not allowed to create categories.');

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
            $discord->channel($this->channelId)->send($this->command['usage']);
            $discord->channel($this->channelId)->send($this->command['example']);

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
            $discord->channel($this->channelId)->send('❌ Failed to create category.');
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

        $discordService = app(DiscordService::class);
        $payload = json_decode($jsonPayload, true);
        $apiResponse = $discordService->post("/guilds/{$this->guildId}/channels", $payload);

        if ($apiResponse->failed()) {
            $discord->channel($this->channelId)->send('❌ Failed to create category.');
            $this->nativeCommandRequest->update([
                'status' => 'discord-api-error',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'Failed to create category.',
                    'status_code' => $apiResponse->status(),
                    'details' => $apiResponse->json(),
                ],
            ]);
            throw new Exception('Failed to create category.');
        }

        // ✅ Send Embedded Confirmation Message
        $discord->channel($this->channelId)->sendEmbed(
            '✅ Category Created!',
            "**Category Name:** 📂 {$categoryName}",
            3447003 // Blue embed
        );

        // 5️⃣ Update the status of the command request
        $this->nativeCommandRequest->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }
}
