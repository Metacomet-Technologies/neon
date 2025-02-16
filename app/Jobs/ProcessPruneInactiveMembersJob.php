<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class ProcessPruneInactiveMembersJob extends ProcessBaseJob implements ShouldQueue
{
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Check if the user has permission to manage members
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanKickMembers($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage members in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage members.',
                statusCode: 403,
            );

            return;
        }

        // Extract number of days
        $parts = explode(' ', trim($this->messageContent));
        $days = $parts[1] ?? null;

        // If no parameters are provided, send help message
        if (! $days) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No number of days provided.',
                statusCode: 400,
            );

            return;
        }

        // Validate input
        if (! ctype_digit($days) || (int) $days < 1) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid number of days.',
            ]);
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No number of days provided.',
                statusCode: 400,
            );

            return;
        }

        // Call the Discord API to prune members
        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . config('discord.token'),
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
            $this->updateNativeCommandRequestComplete();

        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to prune members. Ensure the bot has the correct permissions.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to prune members.',
                statusCode: $response->status(),
                details: $response->json(),
            );
        }
    }
}
