<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessVanishChannelJob extends ProcessBaseJob implements ShouldQueue
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
                message: 'User does not have permission to manage channels.',
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
                message: 'No arguments provided.',
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
                'response' => "❌ Invalid channel format. Please mention a channel.",
            ]);
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel format.',
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
                status: 'discord_api_error',
                message: 'Failed to fetch server roles.',
                statusCode: $guildRolesResponse->status(),
                details: $guildRolesResponse->json(),
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

        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . config('discord.token'),
            'Content-Type' => 'application/json',
        ])->put("https://discord.com/api/v10/channels/{$targetChannelId}/permissions/{$everyoneRoleId}", [

            'type' => 0, // Role permission
            'id' => $everyoneRoleId,
            'deny' => '1024', // VIEW_CHANNEL permission bit
            'allow' => '0',
        ]);

        if ($response->successful()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => 'Channel Vanished',
                'embed_description' => "✅ The channel <#{$targetChannelId}> is now hidden from everyone except admins.",
                'color' => 0x00FF00,
            ]);
            $this->updateNativeCommandRequestComplete();
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => 'Failed to Vanish Channel',
                'embed_description' => '❌ Failed to hide the channel. Ensure the bot has the correct permissions.',
                'color' => 0xFF0000,
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to hide the channel.',
                statusCode: $response->status(),
                details: $response->json(),
            );
        }
    }
}
