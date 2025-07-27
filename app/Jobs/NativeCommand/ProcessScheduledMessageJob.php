<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Carbon\Carbon;
use Exception;

final class ProcessScheduledMessageJob extends ProcessBaseJob
{
    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        $this->requireMemberPermission();

        // Extract parameters from message - format: !schedule-message #channel YYYY-MM-DD HH:MM message
        $content = trim(str_replace('!schedule-message', '', $this->messageContent));

        if (empty($content)) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        // Parse channel, datetime and message
        // Match: #channel_id YYYY-MM-DD HH:MM message...
        preg_match('/^<?#?(\d{17,19})>?\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s+(.+)$/s', $content, $matches);

        if (count($matches) < 4) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid format. Use: !schedule-message #channel YYYY-MM-DD HH:MM message', 400);
        }

        $channelId = $matches[1];
        $datetime = $matches[2];
        $message = $matches[3];

        $scheduledTime = $this->validateDateTime($datetime);

        // Queue the message for later
        $this->scheduleMessage($channelId, $message, $scheduledTime);

        $this->sendSuccessMessage('Message Scheduled', "Message will be sent at {$datetime}");
    }

    private function validateDateTime(string $datetime): Carbon
    {
        try {
            $carbonTime = Carbon::createFromFormat('Y-m-d H:i', $datetime);

            if (! $carbonTime) {
                $this->sendErrorMessage('Invalid datetime format. Use YYYY-MM-DD HH:MM format.');
                throw new Exception('Invalid datetime format.', 400);
            }

            if ($carbonTime->isPast()) {
                $this->sendErrorMessage('Cannot schedule messages in the past.');
                throw new Exception('Cannot schedule messages in the past.', 400);
            }

            return $carbonTime;
        } catch (Exception) {
            $this->sendErrorMessage('Invalid datetime format. Use YYYY-MM-DD HH:MM format.');
            throw new Exception('Invalid datetime format.', 400);
        }
    }

    private function scheduleMessage(string $channelId, string $message, Carbon $scheduledTime): void
    {
        // Create a new job to send the message at the scheduled time
        ProcessScheduledMessageDeliveryJob::dispatch($channelId, $message)->delay($scheduledTime);
    }
}
