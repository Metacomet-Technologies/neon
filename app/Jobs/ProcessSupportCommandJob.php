<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessSupportCommandJob implements ShouldQueue
{
    use Queueable;

    private string $baseUrl;
    private string $supportGuildId = '1300962530096709733';
    private string $supportChannelId = '1336312029841199206';

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }
//TODO: This may or may not work, needs testing. Currently set isactive to false.
    public function handle(): void
    {
        // Remove the command itself from the message
        $supportMessage = trim(str_replace('!support', '', $this->messageContent));

        // Ensure there is a message to send
        if (empty($supportMessage)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Please provide a message with your support request. Example: `!support I need help with my role.`',
            ]);
            return;
        }

        // Format the message
        $messagePayload = [
            'is_embed' => true,
            'embed_title' => '📢 Support Request',
            'embed_description' => "**User:** <@{$this->discordUserId}>\n**Guild ID:** {$this->guildId}\n\n📌 **Message:**\n{$supportMessage}",
            'embed_color' => 3447003, // Blue color
        ];

        // ✅ Ensure Neon sends the message
        $response = Http::withToken(config('services.discord.bot_token'), 'Bot')
            ->post("{$this->baseUrl}/channels/{$this->supportChannelId}/messages", [
                'content' => '',
                'embeds' => [[
                    'title' => $messagePayload['embed_title'],
                    'description' => $messagePayload['embed_description'],
                    'color' => $messagePayload['embed_color'],
                ]],
            ]);

        // Check response
        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to send the support request. Please try again later.',
            ]);
            return;
        }

        // Confirmation to the user
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Support Request Sent!',
            'embed_description' => 'Your request has been forwarded to the support team. They will get back to you soon!',
            'embed_color' => 3066993, // Green color
        ]);
    }
}
