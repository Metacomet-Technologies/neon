<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessKickUserJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !kick <user-id>';
    public string $exampleMessage = 'Example: !kick 123456789012345678';

    private string $baseUrl;
    private string $targetUserId;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender (user executing !kick)
        public string $channelId,     // The channel where the command was sent
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->targetUserId = $this->parseMessage($this->messageContent);

        if (! $this->targetUserId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid user ID.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            throw new Exception('Invalid input for !kick. Expected a valid user ID.');
        }
    }

    public function handle(): void
    {
        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please provide a valid Discord user ID.',
            ]);

            return;
        }

        // Step 1️⃣: Check if the sender has KICK_MEMBERS permission
        if (! $this->userHasKickPermission($this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to kick members.',
            ]);

            return;
        }

        // Step 2️⃣: Check Role Hierarchy
        $senderHighestRole = $this->getUserHighestRole($this->discordUserId);
        $targetUserHighestRole = $this->getUserHighestRole($this->targetUserId);

        if ($senderHighestRole <= $targetUserHighestRole) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You cannot kick this user. Their role is equal to or higher than yours.',
            ]);

            return;
        }

        // Step 3️⃣: Kick the user
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$this->targetUserId}";

        $apiResponse = retry($this->maxRetries, function () use ($url) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($url);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to kick user. They may have already been removed.',
            ]);

            return;
        }

        // Step 4️⃣: Send Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🚪 User Kicked',
            'embed_description' => "✅ <@{$this->targetUserId}> has been kicked from the server.",
            'embed_color' => 15158332, // Red
        ]);
    }

    private function parseMessage(string $message): ?string
    {
        preg_match('/^!kick\s+(<@!?(\d{17,19})>|\d{17,19})$/', $message, $matches);

        return $matches[2] ?? $matches[1] ?? null;
    }

    /**
     * ✅ Check if the sender has the KICK_MEMBERS permission.
     */
    private function userHasKickPermission(string $userId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return false;
        }

        $userData = $response->json();
        $roles = $userData['roles'] ?? [];

        if (empty($roles)) {
            return false;
        }

        // Fetch role permissions
        $rolesUrl = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);

        if ($rolesResponse->failed()) {
            return false;
        }

        $allRoles = collect($rolesResponse->json());

        // Calculate sender's permissions from roles
        $permissionsBitfield = $allRoles->whereIn('id', $roles)->sum('permissions');

        // Fetch server owner ID
        $guildUrl = "{$this->baseUrl}/guilds/{$this->guildId}";
        $guildResponse = Http::withToken(config('discord.token'), 'Bot')->get($guildUrl);

        if ($guildResponse->failed()) {
            return false;
        }

        $guildData = $guildResponse->json();
        $ownerId = $guildData['owner_id'] ?? null;

        // ✅ Allow if the sender is the **server owner**
        if ($ownerId === $userId) {
            return true;
        }

        // ✅ Allow if user has either KICK_MEMBERS or ADMINISTRATOR using enums
        return ($permissionsBitfield & (int) DiscordPermissionEnum::KICK_MEMBERS) === (int) DiscordPermissionEnum::KICK_MEMBERS
            || ($permissionsBitfield & (int) DiscordPermissionEnum::ADMINISTRATOR) === (int) DiscordPermissionEnum::ADMINISTRATOR;
    }

    /**
     * ✅ Fetch the highest role position for a user.
     */
    private function getUserHighestRole(string $userId): int
    {
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return 0;
        }

        $userData = $response->json();
        $roles = $userData['roles'] ?? [];

        if (empty($roles)) {
            return 0;
        }

        $rolesUrl = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);

        if ($rolesResponse->failed()) {
            return 0;
        }

        $allRoles = collect($rolesResponse->json());
        $userRoles = $allRoles->whereIn('id', $roles);

        return $userRoles->max('position') ?? 0;
    }
}
