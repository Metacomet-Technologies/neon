<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessUnvanishChannelJob implements ShouldQueue
{
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'unvanish',
    // 'description' => 'Restores visibility to a previously vanished channel for everyone.',
    // 'class' => \App\Jobs\ProcessUnvanishChannelJob::class,
    // 'usage' => 'Usage: !unvanish <channel>',
    // 'example' => 'Example: !unvanish #general',
    // 'is_active' => true,

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'unvanish')->first();

        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
    }

    public function handle()
    {
        // Extract channel mention
        $parts = explode(' ', trim($this->messageContent));
        $mentionedChannel = $parts[1] ?? null;

        // If no channel is mentioned, show help message
        if (! $mentionedChannel) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Extract channel ID from mention format <#channelID>
        if (preg_match('/<#(\d+)>/', $mentionedChannel, $matches)) {
            $targetChannelId = $matches[1];
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid channel format. Please mention a channel.\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Check if the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserIsAdmin($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage this channel.',
            ]);

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

            return;
        }

        $guildRoles = $guildRolesResponse->json();
        $everyoneRole = collect($guildRoles)->firstWhere('name', '@everyone');

        if (! $everyoneRole) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Could not find the @everyone role in this server.',
            ]);

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
                'is_embed' => false,
                'response' => "✅ The channel <#{$targetChannelId}> is now visible to everyone.",
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to unvanish the channel. Ensure the bot has the correct permissions.',
            ]);
        }
    }
}
