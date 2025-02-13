<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

//TODO: this job may not be muting users as expected. Something about the roles and permissions is off.
final class ProcessMuteUserJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private string $targetUserId; // The user being muted

    private int $retryDelay = 2000; // ✅ 2-second delay before retrying
    private int $maxRetries = 3;    // ✅ Max retries per request

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanMuteMembers($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to mute/unmute users in this server.',
            ]);

            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to mute members',
                statusCode: 403,
            );

            return;
        }

        // Check if the command was sent without any arguments (only "!mute")
        if (trim($this->messageContent) === '!mute') {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No parameters provided.',
                statusCode: 400,
            );
            // Stop further processing by throwing an exception
            throw new Exception('No user ID provided for !mute command.');
        }

        // Parse the message for a valid user ID
        $this->targetUserId = $this->parseMessage($this->messageContent);

        // Validate input
        if (! $this->targetUserId) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid parameters provided.',
                statusCode: 400,
            );
            // Stop execution
            throw new Exception('Invalid input for !mute. Expected a valid user ID.');
        }

        // Ensure the input is a valid Discord user ID
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

        // Step 1️⃣: Fetch all voice channels in the guild
        $channelsUrl = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $channelsResponse = retry($this->maxRetries, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, $this->retryDelay);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild (ID: `{$this->guildId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve server channels.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to fetch channels.',
                statusCode: $channelsResponse->status(),
                details: $channelsResponse->json(),
            );

            return;
        }

        $channels = collect($channelsResponse->json());
        $voiceChannels = $channels->filter(fn ($ch) => $ch['type'] === 2); // Voice channels only

        // Step 2️⃣: Apply mute permission to the user in each voice channel
        $failedChannels = [];

        foreach ($voiceChannels as $channel) {
            $channelId = $channel['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$channelId}/permissions/{$this->targetUserId}";

            $payload = [
                'deny' => (1 << 11), // Deny SPEAK permission
                'type' => 1, // Member override (not role)
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedChannels[] = "<#{$channelId}>";
            }
        }

        // Step 3️⃣: Send Confirmation Message
        if (! empty($failedChannels)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔇 Mute Failed',
                'embed_description' => '❌ Failed to mute user in: ' . implode(', ', $failedChannels),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔇 User Muted',
                'embed_description' => "✅ <@{$this->targetUserId}> has been muted in **all voice channels**.",
                'embed_color' => 3066993, // Green
            ]);
        }
        $this->updateNativeCommandRequestComplete();
    }

    private function parseMessage(string $message): ?string
    {
        // Use regex to extract user ID from mention OR raw ID
        preg_match('/^!mute\s+(<@!?(\d{17,19})>|\d{17,19})$/', $message, $matches);

        // If user ID is wrapped in <@...>, extract only the ID
        return $matches[2] ?? $matches[1] ?? null;
    }
}
