<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessNewCategoryJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !new-category <category-name>';
    public string $exampleMessage = 'Example: !new-category test-category';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $message,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Ensure the user has permission to create categories
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create categories.',
            ]);

            return;
        }

        // 2️⃣ Parse the command
        $parts = explode(' ', $this->message, 2);

        // If not enough parameters, send usage message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        // 3️⃣ Extract the category name
        $categoryName = trim($parts[1]);

        // 4️⃣ Create the category via Discord API
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody(json_encode([
                'name' => $categoryName,
                'type' => Channel::TYPE_GUILD_CATEGORY,
            ]), 'application/json')
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
