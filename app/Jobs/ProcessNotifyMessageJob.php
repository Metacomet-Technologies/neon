<?php

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class ProcessNotifyMessageJob extends ProcessBaseJob implements ShouldQueue
{
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Check if the user has permission to send announcements
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserIsAdmin($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to send announcements.',
            ]);
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to send announcements.',
                statusCode: 403,
            );

            return;
        }

        // Extract target channel, mention, title, and message
        $matches = [];
        preg_match('/^!notify\s+(<#\d+>)\s+(<@!?&?\d+>|@everyone|@here)\s*(.*)$/', $this->messageContent, $matches);

        if (empty($matches)) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        $targetChannelMention = $matches[1] ?? null;
        $mention = $matches[2] ?? null;
        $messageBody = trim($matches[3] ?? '');

        // Extract channel ID from mention format <#channelID>
        if (preg_match('/<#(\d+)>/', $targetChannelMention, $channelMatches)) {
            $targetChannelId = $channelMatches[1];
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel format. Please mention a valid channel.',
            ]);
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel format.',
                statusCode: 400,
            );

            return;
        }

        // Separate title and message using "|"
        [$title, $message] = explode('|', $messageBody, 2) + [null, null];

        $title = trim($title ?? '');
        $message = trim($message ?? '');

        // Default title if none provided
        if (! $message) {
            $message = $title;
            $title = '📢 Announcement';
        }

        // Default color (blue) for embed
        $embedColor = 3447003; // Default Blue
        if (preg_match('/#([0-9A-Fa-f]{6})/', $messageBody, $colorMatch)) {
            $embedColor = hexdec($colorMatch[1]);
            $messageBody = str_replace($colorMatch[0], '', $messageBody); // Remove color code from message
        }

        // Prepare the embed
        $embed = [
            'title' => $title,
            'description' => $message,
            'color' => $embedColor,
        ];

        // Send the announcement message
        $response = Http::withHeaders([
            'Authorization' => 'Bot ' . config('discord.token'),
            'Content-Type' => 'application/json',
        ])->post("https://discord.com/api/v10/channels/{$targetChannelId}/messages", [
            'content' => $mention,
            'embeds' => [$embed],
            'tts' => false, // No text-to-speech
        ]);

        if ($response->successful()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "📢 Announcement sent successfully to <#{$targetChannelId}>!",
            ]);
            $this->updateNativeCommandRequestComplete();
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to send announcement. Ensure the bot has the correct permissions.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to send announcement.',
                statusCode: 500,
            );
        }
    }
}
