<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class ProcessUnvanishChannelJob extends ProcessBaseJob implements ShouldQueue
{
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Check if the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserIsAdmin($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage this channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'You do not have permission to manage this channel.',
                statusCode: 403,
            );

            return;
        }

        // Extract channel mention
        $parts = explode(' ', trim($this->messageContent));
        $mentionedChannel = $parts[1] ?? null;

        // If no channel is mentioned, show help message
        if (! $mentionedChannel) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No channel mentioned.',
                statusCode: 400,
            );

            return;
        }

        // Extract channel ID from mention format <#channelID>
        if (preg_match('/<#(\d+)>/', $mentionedChannel, $matches)) {
            $targetChannelId = $matches[1];
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel format. Please mention a channel.',
            ]);
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: ' Invalid channel format. Please mention a channel.',
                statusCode: 400,
            );

            return;
        }

        // Get @everyone role ID for the guild
        $guildRolesResponse = Http::withHeaders([
            'Authorization' => 'Bot ' . config('discord.token'),
            'Content-Type' => 'application/json',
        ])->get("https://discord.com/api/v10/guilds/{$this->guildId}/roles");

        if (! $guildRolesResponse->successful()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to fetch server roles.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to fetch server roles.',
                statusCode: 500,
            );

            return;
        }

        $guildRoles = $guildRolesResponse->json();
        $everyoneRole = collect($guildRoles)->firstWhere('name', '@everyone');

        if (! $everyoneRole) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Could not find the @everyone role in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Could not find the @everyone role in this server.',
                statusCode: 404,
            );

            return;
        }

        $everyoneRoleId = $everyoneRole['id'];

        // Restore channel visibility by allowing VIEW_CHANNEL for @everyone
        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . config('discord.token'),
            'Content-Type' => 'application/json',
        ])->put("https://discord.com/api/v10/channels/{$targetChannelId}/permissions/{$everyoneRoleId}", [
            'type' => 0, // Role permission
            'id' => $everyoneRoleId,
            'deny' => '0', // Remove VIEW_CHANNEL restriction
            'allow' => '1024', // Allow VIEW_CHANNEL
        ]);

        if ($response->successful()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => 'Channel Unvanished',
                'embed_description' => "✅ The channel <#{$targetChannelId}> is now visible to everyone.",
                'color' => 0x00FF00,
            ]);
            $this->updateNativeCommandRequestComplete();
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => 'Unvanish Failed',
                'embed_description' => '❌ Failed to unvanish the channel. Ensure the bot has the correct permissions.',
                'color' => 0xFF0000,
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to unvanish the channel. Ensure the bot has the correct permissions.',
                details: $response->json(),
                statusCode: $response->status(),
            );
        }
    }
}
