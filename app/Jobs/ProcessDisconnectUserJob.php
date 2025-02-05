<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDisconnectUserJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage;
    public string $exampleMessage;

        // 'slug' => 'disconnect',
        // 'description' => 'Disconnects one or more users from a voice channel.',
        // 'class' => \App\Jobs\ProcessDisconnectUserJob::class,
        // 'usage' => 'Usage: !disconnect <@user1> [@user2] ...',
        // 'example' => 'Example: !disconnect @User1 @User2',
        // 'is_active' => true,

    public string $baseUrl;
    private array $targetUserIds = [];

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'disconnect')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        $this->targetUserIds = $this->parseMessage($this->messageContent);
    }
//TODO: May want to add logic to have channel id instead of user, which would disonnect all users in that channel.
    public function handle(): void
    {
        // 🚨 **Moved validation here to ensure job does not execute unnecessarily**
        if (empty($this->targetUserIds)) {
            Log::warning("Disconnect command used without target users.");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Check if user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to disconnect users from voice channels in this server.',
            ]);

            return;
        }

        $failedUsers = [];

        // Disconnect each user from their current voice channel
        foreach ($this->targetUserIds as $userId) {
            $kickUrl = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";

            $response = retry($this->maxRetries, function () use ($kickUrl) {
                return Http::withToken(config('discord.token'), 'Bot')->patch($kickUrl, ['channel_id' => null]);
            }, $this->retryDelay);

            if ($response->failed()) {
                Log::error("Failed to disconnect user {$userId} from voice channel.");
                $failedUsers[] = "<@{$userId}>";
            }
        }

        // Send response message
        if (! empty($failedUsers)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Disconnect Failed',
                'embed_description' => 'Failed to remove: ' . implode(', ', $failedUsers),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '✅ Users Disconnected from Voice Channel',
                'embed_description' => 'Successfully disconnected users from voice chat.',
                'embed_color' => 3066993, // Green
            ]);
        }
    }

    private function parseMessage(string $message): array
    {
        preg_match_all('/<@!?(\d{17,19})>/', $message, $matches);
        return $matches[1] ?? [];
    }
}
