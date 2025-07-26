<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\CommandAnalyticsService;
use Illuminate\Support\Facades\Log;

final class ProcessScheduledMessageDeliveryJob extends ProcessBaseJob
{
    public function __construct(
        public string $targetChannelId,
        public string $messageContent
    ) {
        // This job doesn't extend ProcessBaseJob for message sending since it's just delivery
        parent::__construct('', '', '', '', [], '', []);
    }

    public function handle(CommandAnalyticsService $analytics): void
    {
        $success = $this->discord->sendMessage($this->targetChannelId, $this->messageContent);

        if (! $success) {
            // Log the failure but don't throw exception since user isn't waiting
            Log::error('Failed to send scheduled message', [
                'channel_id' => $this->targetChannelId,
                'message' => $this->messageContent,
            ]);
        }
    }
}
