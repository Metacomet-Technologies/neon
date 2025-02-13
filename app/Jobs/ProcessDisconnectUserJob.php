<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDisconnectUserJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private array $targetUserIds = [];

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    //TODO: May want to add logic to have channel id instead of user, which would disonnect all users in that channel.
    public function handle(): void
    {
        // Parse the message
        $this->targetUserIds = $this->parseMessage($this->messageContent);

        // 🚨 **Moved validation here to ensure job does not execute unnecessarily**
        if (empty($this->targetUserIds)) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Check if user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to disconnect users from voice channels in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels.',
                statusCode: 403,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to disconnect users from voice channel.',
                statusCode: 500,
            );
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '✅ Users Disconnected from Voice Channel',
                'embed_description' => 'Successfully disconnected users from voice chat.',
                'embed_color' => 3066993, // Green
            ]);
        }
        $this->updateNativeCommandRequestComplete();
    }

    private function parseMessage(string $message): array
    {
        preg_match_all('/<@!?(\d{17,19})>/', $message, $matches);

        return $matches[1] ?? [];
    }
}
