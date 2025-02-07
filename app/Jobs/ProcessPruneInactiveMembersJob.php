<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessPruneInactiveMembersJob
{
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'prune',
    // 'description' => 'Kicks members inactive for the specified number of days.',
    // 'class' => \App\Jobs\ProcessPruneInactiveMembersJob::class,
    // 'usage' => 'Usage: !prune <days>',
    // 'example' => 'Example: !prune 30',
    // 'is_active' => true,

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'prune')->first();

        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
    }

    public function handle()
    {
        // Extract number of days
        $parts = explode(' ', trim($this->messageContent));
        $days = $parts[1] ?? null;

        // If no parameters are provided, send help message
        if (! $days) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Validate input
        if (! ctype_digit($days) || (int) $days < 1) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid number of days.\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Check if the user has permission to manage members
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanKickMembers($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage members in this server.',
            ]);

            return;
        }

        // Call the Discord API to prune members
        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . env('DISCORD_BOT_TOKEN'),
            'Content-Type' => 'application/json',
        ])->post("https://discord.com/api/v10/guilds/{$this->guildId}/prune", [
            'days' => (int) $days,
            'compute_prune_count' => true,
        ]);

        // Handle API response
        if ($response->successful()) {
            $data = $response->json();
            $prunedCount = $data['pruned'] ?? 0;

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "✅ Successfully pruned {$prunedCount} inactive members from the server.",
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to prune members. Ensure the bot has the correct permissions.',
            ]);
        }
    }
}
