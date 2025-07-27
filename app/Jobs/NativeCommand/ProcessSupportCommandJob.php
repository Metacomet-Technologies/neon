<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessSupportCommandJob extends ProcessBaseJob
{
    private const SUPPORT_GUILD_ID = '1300962530096709733';
    private const SUPPORT_CHANNEL_ID = '1336312029841199206';

    protected function executeCommand(): void
    {
        // Parse support message
        $supportMessage = trim(str_replace('!support', '', $this->messageContent));

        // Validate message
        if (empty($supportMessage)) {
            $this->sendErrorMessage('Please provide a message with your support request. Example: `!support I need help with my role.`');
            throw new Exception('Empty support message.', 400);
        }

        // Send support request to support channel
        $embedDescription = "**User:** <@{$this->discordUserId}>\n" .
                          "**Guild ID:** {$this->guildId}\n\n" .
                          "📌 **Message:**\n{$supportMessage}";

        $success = $this->getDiscord()->channel(self::SUPPORT_CHANNEL_ID)->sendEmbed(
            '📢 Support Request',
            $embedDescription,
            3447003 // Blue
        );

        if (! $success) {
            $this->sendApiError('send support request');
            throw new Exception('Failed to send support request.', 500);
        }

        // Send confirmation to user
        $this->sendSuccessMessage(
            'Support Request Sent!',
            'Your request has been forwarded to the support team. They will get back to you soon!',
            3066993 // Green
        );
    }
}
