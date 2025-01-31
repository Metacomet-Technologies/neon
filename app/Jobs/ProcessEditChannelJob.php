<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !edit-channel <channel-id> name:<new-name> topic:<new-topic> slowmode:<seconds> nsfw:<true|false> autohide:<minutes>';
    public string $exampleMessage = 'Example: !edit-channel 123456789012345678 name:general topic:"New discussion" slowmode:5 nsfw:true autohide:1440';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $message,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Ensure the user has permission to edit channels
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to edit channels.',
            ]);

            return;
        }

        // 2️⃣ Parse command using regex for labeled parameters
        preg_match('/^!edit-channel\s+(\d+)(?:\s+name:([^\s]+))?(?:\s+topic:"([^"]+)")?(?:\s+slowmode:(\d+))?(?:\s+nsfw:(true|false))?(?:\s+autohide:(\d+))?$/i', $this->message, $matches);

        if (! isset($matches[1])) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        $channelId = $matches[1]; // Channel ID (required)
        $newName = $matches[2] ?? null; // New channel name (optional)
        $newTopic = $matches[3] ?? null; // New channel topic (optional)
        $slowmode = isset($matches[4]) ? intval($matches[4]) : null; // Slow mode (optional)
        $nsfw = isset($matches[5]) ? filter_var($matches[5], FILTER_VALIDATE_BOOLEAN) : null; // NSFW flag (optional)
        $autoHide = isset($matches[6]) ? intval($matches[6]) : null; // Auto-hide (optional, for threads only)

        // 3️⃣ Validate slow mode (must be between 0-21600 seconds)
        if ($slowmode !== null && ($slowmode < 0 || $slowmode > 21600)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid slow mode delay. Must be between 0 and 21600 seconds.',
            ]);

            return;
        }

        // 4️⃣ Validate auto-hide (valid options: 60, 1440, 4320, 10080 minutes)
        $validAutoHideValues = [60, 1440, 4320, 10080];
        if ($autoHide !== null && ! in_array($autoHide, $validAutoHideValues)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid auto-hide duration. Use 60, 1440, 4320, or 10080 minutes.',
            ]);

            return;
        }

        // 5️⃣ Construct the API request payload
        $payload = [];

        if ($newName) {
            $payload['name'] = $newName;
        }

        if ($newTopic) {
            $payload['topic'] = $newTopic;
        }

        if ($slowmode !== null) {
            $payload['rate_limit_per_user'] = $slowmode;
        }

        if ($nsfw !== null) {
            $payload['nsfw'] = $nsfw;
        }

        if ($autoHide !== null) {
            $payload['default_auto_archive_duration'] = $autoHide;
        }

        if (empty($payload)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ No changes specified. Please provide at least one field to edit.',
            ]);

            return;
        }

        $url = $this->baseUrl . "/channels/{$channelId}";

        // 6️⃣ Send request to edit the channel
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to edit channel '{$channelId}' in guild {$this->guildId}", [
                'response' => $apiResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to edit channel (ID: `{$channelId}`).",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Edited!',
            'embed_description' => "**Channel ID:** `{$channelId}`\n"
                . ($newName ? "**New Name:** #{$newName}\n" : '')
                . ($newTopic ? "**New Topic:** 📝 `{$newTopic}`\n" : '')
                . ($slowmode !== null ? "**Slowmode:** ⏳ `{$slowmode} sec`\n" : '')
                . ($nsfw !== null ? '**NSFW:** 🔞 `' . ($nsfw ? 'Enabled' : 'Disabled') . "`\n" : '')
                . ($autoHide !== null ? "**Auto-hide:** ⏲️ `{$autoHide} minutes`\n" : ''),
            'embed_color' => 3447003, // Blue embed
        ]);
    }
}
