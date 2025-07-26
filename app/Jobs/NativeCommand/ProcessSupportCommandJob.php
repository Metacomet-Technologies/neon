<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

// TODO: this may not work, needs testing. Currently set isactive to false. not updateded with processbasejob
final class ProcessSupportCommandJob extends ProcessBaseJob
{
    private string $supportGuildId = '1300962530096709733';
    private string $supportChannelId = '1336312029841199206';

    protected function executeCommand(): void
    {
        // Remove the command itself from the message
        $supportMessage = trim(str_replace('!support', '', $this->messageContent));

        // Ensure there is a message to send
        if (empty($supportMessage)) {
            $discord = new Discord;
            $discord->channel($this->channelId)->send('❌ Please provide a message with your support request. Example: `!support I need help with my role.`');

            return;
        }

        // Format the message
        $messagePayload = [
            'is_embed' => true,
            'embed_title' => '📢 Support Request',
            'embed_description' => "**User:** <@{$this->discordUserId}>\n**Guild ID:** {$this->guildId}\n\n📌 **Message:**\n{$supportMessage}",
            'embed_color' => 3447003, // Blue color
        ];

        // ✅ Ensure Neon sends the message using Discord SDK
        try {
            $discord = new Discord;
            $discord->channel($this->supportChannelId)->sendEmbed(
                $messagePayload['embed_title'],
                $messagePayload['embed_description'],
                $messagePayload['embed_color']
            );
        } catch (\Exception $e) {
            $discord = new Discord;
            $discord->channel($this->channelId)->send('❌ Failed to send the support request. Please try again later.');

            return;
        }

        // Confirmation to the user
        $discord = new Discord;
        $discord->channel($this->channelId)->sendEmbed(
            '✅ Support Request Sent!',
            'Your request has been forwarded to the support team. They will get back to you soon!',
            3066993 // Green color
        );
    }
}
