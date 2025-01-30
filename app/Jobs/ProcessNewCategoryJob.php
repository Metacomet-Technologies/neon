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

        // Let's make sure you are an admin on the server
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage('You are not allowed to create categories.', $this->channelId);

            return;
        }

        // Split the message content into parts
        $parts = explode(' ', $this->message, 2);

        // does the contain enough parameters
        // if not send the usage message and example message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->usageMessage, $this->channelId);
            SendMessage::sendMessage($this->exampleMessage, $this->channelId);

            return;
        }

        // Extract the category name from the message content
        $categoryName = $parts[1];

        // Let's create the category
        $url = $this->baseUrl . '/guilds/' . $this->guildId . '/channels';
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody(json_encode([
                'name' => $categoryName,
                'type' => Channel::TYPE_GUILD_CATEGORY,
            ]), 'application/json')
            ->post($url);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage('Failed to create category.', $this->channelId);
            throw new Exception('Failed to create category.');
        }

        SendMessage::sendMessage('Category created successfully.', $this->channelId);
    }
}
