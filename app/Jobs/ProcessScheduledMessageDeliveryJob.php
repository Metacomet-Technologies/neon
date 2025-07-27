<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessScheduledMessageDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $targetChannelId,
        public string $messageContent
    ) {}

    public function handle(): void
    {
        try {
            $discord = app(DiscordService::class);
            $discord->channel($this->targetChannelId)->send($this->messageContent);
        } catch (Exception $e) {
            // Log the failure but don't throw exception since user isn't waiting
            Log::error('Failed to send scheduled message', [
                'channel_id' => $this->targetChannelId,
                'message' => $this->messageContent,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
