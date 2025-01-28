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

        // Let's make sure you are an admin on the server
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage('You are not allowed to create channels.', $this->channelId);

            return;
        }

        // Split the message content into parts
        $parts = explode(' ', $this->message);

        // does the contain enough parameters
        // if not send the usage message and example message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->usageMessage, $this->channelId);
            SendMessage::sendMessage($this->exampleMessage, $this->channelId);

            return;
        }

        // Extract the channel name from the message content
        $channelName = $parts[1];

        // If the channel name is one of the channel types, most likely a mistake
        if (in_array($channelName, $this->channelTypes)) {
            SendMessage::sendMessage('Invalid channel name. Please use a different name.', $this->channelId);
            SendMessage::sendMessage($this->usageMessage, $this->channelId);
            SendMessage::sendMessage($this->exampleMessage, $this->channelId);

            return;
        }

        // Validate the channel name is valid
        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            SendMessage::sendMessage($validationResult['message'], $this->channelId);

            return;
        }

        // Extract the channel type from the message content if not provided
        // Default to 'text' if not provided
        $channelType = $parts[2] ?? 'text';

        // If the channel type is not text or voice, send an error message
        if (! in_array($channelType, $this->channelTypes)) {
            SendMessage::sendMessage('Invalid channel type. Please use "text" or "voice".', $this->channelId);

            return;
        }

        // Let's create the channel
        $url = $this->baseUrl . '/guilds/' . $this->guildId . '/channels';
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody(json_encode([
                'name' => $channelName,
                'type' => $channelType === 'text' ? Channel::TYPE_GUILD_TEXT : Channel::TYPE_GUILD_VOICE,
            ]), 'application/json')
            ->post($url);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage('Failed to create channel.', $this->channelId);
            throw new Exception('Failed to create channel.');
        }

        SendMessage::sendMessage('Channel created successfully.', $this->channelId);
    }
}
