<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\SendMessage;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $discordUserId;
    public string $channelId;
    public string $guildId;
    public string $messageContent;
    public array $command;

    /**
     * Create a new job instance.
     */
    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        // Fetch command details from the database
        $this->discordUserId = $nativeCommandRequest->discord_user_id;
        $this->channelId = $nativeCommandRequest->channel_id;
        $this->guildId = $nativeCommandRequest->guild_id;
        $this->messageContent = $nativeCommandRequest->message_content;
        $this->command = $nativeCommandRequest->command ?? [];
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        $this->updateNativeCommandRequestComplete();
    }

    public function updateNativeCommandRequestComplete(): void
    {
        $this->nativeCommandRequest->update([
            'status' => 'completed',
            'executed_at' => now(),
        ]);
    }

    public function sendUsageAndExample(?string $additionalInfo = null): void
    {
        $response = $this->command['usage'] . "\n" . $this->command['example'];
        if ($additionalInfo) {
            $response .= "\n\n" . $additionalInfo;
        }
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => $response,
        ]);
    }

    public function updateNativeCommandRequestFailed(
        string $status,
        string $message,
        mixed $details = null,
        int $statusCode = 500,
        bool $unicorn = false
    ): void {
        $params = [
            'status' => $status,
            'failed_at' => now(),
            'error_message' => [
                'message' => $message,
                'status_code' => $statusCode,
            ],
        ];
        if ($details) {
            $params['error_message']['details'] = $details;
        }
        if ($unicorn) {
            $params['error_message']['unicorn'] = '🦄';
        }

        $this->nativeCommandRequest->update($params);
    }
}
