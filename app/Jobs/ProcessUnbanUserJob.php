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

final class ProcessUnbanUserJob extends ProcessBaseJob implements ShouldQueue
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
        $this->targetUserId = $this->parseMessage($this->messageContent);

        // 🚨 **Validation: Show help message if no arguments are provided**
        if (empty(trim($this->messageContent)) || $this->targetUserId === null) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No arguments provided.',
                statusCode: 400,
            );
        }

        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please provide a valid Discord user ID.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid user ID format.',
                statusCode: 400,
            );

            return;
        }

        // Step 1️⃣: Check if the sender has BAN_MEMBERS permission
        if (! $this->userHasBanPermission($this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to unban members.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to unban members.',
                statusCode: 403,
            );

            return;
        }

        // Step 2️⃣: Check if the user is already unbanned
        if (! $this->isUserBanned($this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ This user is not currently banned.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'User is not currently banned.',
                statusCode: 400,
            );

            return;
        }

        // Step 3️⃣: Unban the user
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/bans/{$this->targetUserId}";

        $apiResponse = retry($this->maxRetries, function () use ($url) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($url);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to unban user. They may have already been unbanned.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to unban user.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );

            return;
        }

        // Step 4️⃣: Send Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🔓 User Unbanned',
            'embed_description' => "✅ <@{$this->targetUserId}> has been unbanned from the server.",
            'embed_color' => 3066993, // Green
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    private function parseMessage(string $message): ?string
    {
        // Normalize spaces and remove mention formatting
        $cleanedMessage = preg_replace('/\s+/', ' ', trim($message));
        $cleanedMessage = str_replace(['<@!', '<@', '>'], '', $cleanedMessage);

        // Extract the user ID
        if (preg_match('/^!unban\s+(\d{17,19})$/', $cleanedMessage, $matches)) {
            return $matches[1];
        }

        return null;
    }

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

        // Sum up all role permissions for the user
        $permissionsBitfield = collect($allRoles)
            ->whereIn('id', $roles)
            ->sum(fn ($role) => (int) $role['permissions']);

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

        // ✅ Allow if user has **BAN_MEMBERS** or **ADMINISTRATOR**
        return ($permissionsBitfield & (int) DiscordPermissionEnum::BAN_MEMBERS) === (int) DiscordPermissionEnum::BAN_MEMBERS
            || ($permissionsBitfield & (int) DiscordPermissionEnum::ADMINISTRATOR) === (int) DiscordPermissionEnum::ADMINISTRATOR;
    }

    private function isUserBanned(string $userId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/bans/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        return $response->ok();
    }
}
