<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\DiscordParserService;
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

        $parsed = DiscordParserService::parseScheduledMessageCommand($this->messageContent);
        if (! $parsed['channel_id'] || ! $parsed['datetime'] || ! $parsed['message']) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($parsed['channel_id']);
        $scheduledTime = $this->validateDateTime($parsed['datetime']);

        // Queue the message for later
        $this->scheduleMessage($parsed['channel_id'], $parsed['message'], $scheduledTime);

        $this->sendSuccessMessage('Message Scheduled', "Message will be sent at {$parsed['datetime']}");
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
        } catch (Exception $e) {
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
