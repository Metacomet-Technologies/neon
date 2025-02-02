<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessPurgeMessagesJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !purge #channel <number>';
    public string $exampleMessage = 'Example: !purge #general 100';

    private string $baseUrl;
    private string $targetChannelId;
    private int $messageCount;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender (user executing !purge)
        public string $channelId,     // The channel where the command was sent
        public string $guildId,       // The guild (server) ID
        public string $messageContent, // The raw message content
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        [$this->targetChannelId, $this->messageCount] = $this->parseMessage($this->messageContent);

        if (!$this->targetChannelId || !$this->messageCount) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);
            throw new Exception('Invalid input for !purge. Expected a valid channel and number of messages.');
        }
    }

    public function handle(): void
    {
        // Check if user is admin using the ADMINISTRATOR permission (enum check)
        if ($this->userHasAdminPermission($this->discordUserId)) {
            $this->purgeMessages();
            return;
        }

        if ($this->userHasManageMessagesPermission($this->discordUserId)) {
            $this->purgeMessages();
            return;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => '❌ You do not have permission to purge messages.',
        ]);
    }

    private function parseMessage(string $message): array
    {
        preg_match('/^!purge\s+<#?(\d{17,19})>\s+(\d+)$/', $message, $matches);

        return isset($matches[1], $matches[2]) ? [$matches[1], (int) $matches[2]] : [null, null];
    }

    private function userHasAdminPermission(string $userId): bool
    {
        dump('Checking if user has ADMINISTRATOR permission.');

        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            dump('Failed to fetch user data. Response:', $response->body());
            return false;
        }

        $userData = $response->json();
        dump('User data for ADMINISTRATOR check:', $userData);

        // Check if the permissions field exists
        if (isset($userData['permissions'])) {
            dump('User permissions bitfield for ADMINISTRATOR check:', $userData['permissions']);
        } else {
            dump('No permissions field in the response for ADMINISTRATOR check.');
        }

        // Check if the user has the ADMINISTRATOR permission (bitfield 8)
        if (isset($userData['permissions']) && ($userData['permissions'] & (int) DiscordPermissionEnum::ADMINISTRATOR->value) === (int) DiscordPermissionEnum::ADMINISTRATOR->value) {
            dump('User has ADMINISTRATOR permission.');
            return true;
        }

        dump('User does not have ADMINISTRATOR permission.');
        return false;
    }


    private function userHasManageMessagesPermission(string $userId): bool
    {
        dump('Checking if user has MANAGE_MESSAGES permission.');

        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            dump('Failed to fetch user data for permission check. Response:', $response->body());
            return false;
        }

        $userData = $response->json();
        dump('User data for MANAGE_MESSAGES check:', $userData);

        // Check if the permissions field exists
        if (isset($userData['permissions'])) {
            dump('User permissions bitfield for MANAGE_MESSAGES check:', $userData['permissions']);
        } else {
            dump('No permissions field in the response for MANAGE_MESSAGES check.');
        }

        // Check if the user has the MANAGE_MESSAGES permission (bitfield 0x2000)
        return isset($userData['permissions']) && ($userData['permissions'] & (int) DiscordPermissionEnum::MANAGE_MESSAGES->value) === (int) DiscordPermissionEnum::MANAGE_MESSAGES->value;
    }



    private function purgeMessages(): void
    {
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/bulk-delete";
        $response = Http::withToken(config('discord.token'), 'Bot')->post($url, [
            'messages' => $this->messageCount,
        ]);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve messages. Please try again later.',
            ]);
            return;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🧹 Messages Purged',
            'embed_description' => "✅ Successfully purged {$this->messageCount} messages from <#{$this->targetChannelId}>.",
            'embed_color' => 3066993,
        ]);
    }
}
