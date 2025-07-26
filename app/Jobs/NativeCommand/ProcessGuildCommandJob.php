<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;
use Illuminate\Support\Facades\Log;

// TODO: check this file to make sure it is still functional as intended

final class ProcessGuildCommandJob extends ProcessBaseJob
{
    public string $baseUrl;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $command
     * @return void
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
        public array $command,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(CommandAnalyticsService $analytics): void
    {
        try {
            $discord = new Discord;

            // Check if command contains embed data
            if (isset($this->command['is_embed']) && $this->command['is_embed']) {
                $discord->channel($this->channelId)->sendEmbed(
                    $this->command['embed_title'] ?? '',
                    $this->command['embed_description'] ?? '',
                    $this->command['embed_color'] ?? 0
                );
            } else {
                $discord->channel($this->channelId)->send($this->command['response'] ?? $this->messageContent);
            }

            Log::info('Sent message to Discord', [
                'channel_id' => $this->channelId,
                'message' => $this->messageContent,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send message to Discord', [
                'channel_id' => $this->channelId,
                'message' => $this->messageContent,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to send message to Discord');
        }
    }
}
