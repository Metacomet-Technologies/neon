<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessBanUserJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;
    private ?string $targetUserId = null;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Step 1️⃣: Check if the sender has BAN_MEMBERS permission
        if (! $this->userHasBanPermission($this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to ban members.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to ban members.',
                statusCode: 403,
            );

            return;
        }

        // Parse the message for target user ID
        $this->targetUserId = $this->parseMessage();

        // ✅ Validate Input - If no user ID is provided, send a usage message and exit
        if (! $this->targetUserId) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );
            return;
        }

        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please provide a valid Discord user ID.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid user ID format.',
                details: $this->targetUserId,
                statusCode: 400,
            );

            return;
        }

        // Step 2️⃣: Check Role Hierarchy
        $senderHighestRole = $this->getUserHighestRole($this->discordUserId);
        $targetUserHighestRole = $this->getUserHighestRole($this->targetUserId);

        if ($senderHighestRole <= $targetUserHighestRole) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You cannot ban this user. Their role is equal to or higher than yours.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to ban peer or higher members.',
                statusCode: 403,
            );

            return;
        }

        // Step 3️⃣: Ban the user
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/bans/{$this->targetUserId}";

        $apiResponse = retry($this->maxRetries, function () use ($url) {
            return Http::withToken(config('discord.token'), 'Bot')->put($url, ['delete_message_days' => 7]);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to ban user. They may have already been banned.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord-api-error',
                message: 'Failed to ban user.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

            return;
        }

        // Step 4️⃣: Send Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🔨 User Banned',
            'embed_description' => "✅ <@{$this->targetUserId}> has been permanently banned from the server.",
            'embed_color' => 15158332, // Red
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    private function parseMessage(): ?string
    {
        preg_match('/^!ban\s+(<@!?(\d{17,19})>|\d{17,19})$/', $this->messageContent, $matches);
        return $matches[2] ?? $matches[1] ?? null;
    }

    /**
     * ✅ Check if the sender has the BAN_MEMBERS permission.
     */
    private function userHasBanPermission(string $userId): bool
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

        // ✅ Allow if user has either BAN_MEMBERS or ADMINISTRATOR using enums
        return ($permissionsBitfield & (int) DiscordPermissionEnum::BAN_MEMBERS) === (int) DiscordPermissionEnum::BAN_MEMBERS
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
