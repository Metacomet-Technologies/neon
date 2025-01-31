<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Helpers\DiscordChannelValidator;
use Discord\Parts\Channel\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessNewChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !new-channel <channel-name> <channel-type>';
    public string $exampleMessage = 'Example: !new-channel test-channel text';
    public array $channelTypes = ['text', 'voice'];

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
        // 1️⃣ Ensure the user has permission to create channels
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create channels.',
            ]);
            return;
        }

        // 2️⃣ Parse the command
        $parts = explode(' ', $this->message);

        // If not enough parameters, send usage message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);
            return;
        }

        // 3️⃣ Extract the channel name
        $channelName = $parts[1];

        // If the channel name is one of the channel types, it's most likely a mistake
        if (in_array($channelName, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Invalid channel name. Please use a different name.']);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);
            return;
        }

        // 4️⃣ Validate the channel name
        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $validationResult['message']]);
            return;
        }

        // 5️⃣ Extract the channel type (default: text)
        $channelType = $parts[2] ?? 'text';

        // If the channel type is invalid, send an error message
        if (! in_array($channelType, $this->channelTypes)) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Invalid channel type. Please use "text" or "voice".']);
            return;
        }

        // 6️⃣ Create the channel
        $url = $this->baseUrl . '/guilds/' . $this->guildId . '/channels';
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody(json_encode([
                'name' => $channelName,
                'type' => $channelType === 'text' ? Channel::TYPE_GUILD_TEXT : Channel::TYPE_GUILD_VOICE,
            ]), 'application/json')
            ->post($url);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Failed to create channel.']);
            throw new Exception('Failed to create channel.');
        }

        // ✅ Send Embedded Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Created!',
            'embed_description' => "**Channel Name:** #{$channelName}\n**Type:** " . ($channelType === 'text' ? '💬 Text' : '🔊 Voice'),
            'embed_color' => 3447003, // Blue embed
        ]);
    }
}
