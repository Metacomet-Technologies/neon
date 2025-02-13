<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessLockChannelJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?string $targetChannelId = null;
    private ?bool $lockStatus = null;
    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the message
        [$this->targetChannelId, $this->lockStatus] = $this->parseMessage($this->messageContent);

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to lock channels in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels',
                statusCode: 403,
            );

            return;
        }

        // If the message is just "!lock-channel" with no additional arguments, send help
        if (trim($this->messageContent) === '!lock-channel') {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No parameters provided.',
                statusCode: 400,
            );

            return;
        }

        // Ensure proper usage message is sent if no parameters are provided
        if (! $this->targetChannelId || ! is_bool($this->lockStatus)) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid parameters provided.',
                statusCode: 400,
            );


            return;
        }

        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Retrieve all roles in the guild
        $rolesUrl = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $rolesResponse = retry($this->maxRetries, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, $this->retryDelay);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to rename channel.',
                statusCode: $rolesResponse->status(),
                details: $rolesResponse->json(),
            );

            return;
        }

        $roles = $rolesResponse->json();
        $failedRoles = [];

        // Lock or Unlock the channel by updating permissions for all roles
        foreach ($roles as $role) {
            $roleId = $role['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/permissions/{$roleId}";

            $payload = [
                'deny' => $this->lockStatus ? (1 << 11) : 0, // Deny or allow SEND_MESSAGES
                'type' => 0, // Role
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedRoles[] = $role['name'];
            }
        }

        // Send response message
        if (! empty($failedRoles)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => $this->lockStatus ? '🔒 Lock Channel Failed' : '🔓 Unlock Channel Failed',
                'embed_description' => '❌ Failed for roles: ' . implode(', ', $failedRoles),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => $this->lockStatus ? '🔒 Channel Locked' : '🔓 Channel Unlocked',
                'embed_description' => "✅ Channel <#{$this->targetChannelId}> has been " . ($this->lockStatus ? 'locked' : 'unlocked') . '.',
                'embed_color' => $this->lockStatus ? 15158332 : 3066993, // Red for lock, Green for unlock
            ]);
        }
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to extract the channel ID or mention and lock/unlock flag
        preg_match('/^!lock-channel\s+(<#\d{17,19}>|\d{17,19})\s+(true|false)$/i', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Invalid input
        }

        $channelIdentifier = trim($matches[1]);
        $lockStatus = strtolower(trim($matches[2])) === 'true'; // Convert to boolean

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1];
        }

        return [$channelIdentifier, $lockStatus];
    }
}
