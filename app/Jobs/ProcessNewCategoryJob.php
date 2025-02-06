<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ProcessNewCategoryJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'new-category',
    // 'description' => 'Creates a new category in the server.',
    // 'class' => \App\Jobs\ProcessNewCategoryJob::class,
    // 'usage' => 'Usage: !new-category <category-name>',
    // 'example' => 'Example: !new-category test-category',
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
        $command = DB::table('native_commands')->where('slug', 'new-category')->first();

        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

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
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
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

            return;
        }

        // 2️⃣ Parse the command: check for command with missing parameters
        $parts = explode(' ', $this->messageContent, 2);
        if (count($parts) < 2) {
            // Send the usage and example messages if no category name is provided.
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
            throw new Exception('Failed to create category.');
        }

        // ✅ Send Embedded Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Category Created!',
            'embed_description' => "**Category Name:** 📂 {$categoryName}",
            'embed_color' => 3447003, // Blue embed
        ]);
    }
}
