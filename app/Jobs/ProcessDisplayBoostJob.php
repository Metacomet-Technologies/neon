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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDisplayBoostJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?bool $displayBoost = null; // Whether to enable or disable the boost bar

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the message
        $this->displayBoost = $this->parseMessage($this->messageContent);

        // ❌ If parsing failed, send an error message and stop execution
        if ($this->displayBoost === null) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            // Stop job execution
            throw new Exception('Invalid input for !display-boost. Expected true or false.');
        }

        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage boost display in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage boost display.',
                statusCode: 403,
            );

            return;
        }
        // Build API request
        $url = "{$this->baseUrl}/guilds/{$this->guildId}";
        $payload = ['premium_progress_bar_enabled' => $this->displayBoost];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update boost progress bar for guild (ID: `{$this->guildId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update boost progress bar.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to update boost progress bar.',
                statusCode: 500,
            );

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🚀 Boost Progress Bar Updated',
            'embed_description' => $this->displayBoost
                ? '✅ Server Boost Progress Bar is now **enabled**.'
                : '❌ Server Boost Progress Bar is now **disabled**.',
            'embed_color' => $this->displayBoost ? 3447003 : 15158332, // Blue for enabled, Red for disabled
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): ?bool
    {
        // Use regex to extract true/false parameter
        preg_match('/^!display-boost\s+(true|false)$/i', $message, $matches);

        if (! isset($matches[1])) {
            return null;
        }

        return strtolower(trim($matches[1])) === 'true';
    }
}
