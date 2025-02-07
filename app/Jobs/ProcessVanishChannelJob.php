<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessVanishChannelJob
{
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'vanish',
    // 'description' => 'Hides a text channel for everyone but admins.',
    // 'class' => \App\Jobs\ProcessVanishChannelJob::class,
    // 'usage' => 'Usage: !vanish <channel>',
    // 'example' => 'Example: !vanish #general',
    // 'is_active' => true,

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'vanish')->first();

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
            'Authorization' => 'Bot ' . env('DISCORD_BOT_TOKEN'),
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

        // 🔹 THIS IS WHERE YOU REPLACE YOUR EXISTING API CALL WITH THE DEBUGGING CODE:
        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . env('DISCORD_BOT_TOKEN'),
            'Content-Type' => 'application/json',
        ])->put("https://discord.com/api/v10/channels/{$targetChannelId}/permissions/{$everyoneRoleId}", [

            'type' => 0, // Role permission
            'id' => $everyoneRoleId,
            'deny' => '1024', // VIEW_CHANNEL permission bit
            'allow' => '0',
        ]);

        // Dump response for debugging
        dump($response->json());

        if ($response->successful()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "✅ The channel <#{$targetChannelId}> is now hidden from everyone except admins.",
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to hide the channel. Ensure the bot has the correct permissions.',
            ]);

            // Dump full error details for debugging
            dump('❌ Discord API Response:', $response->json());
        }
    }
}
