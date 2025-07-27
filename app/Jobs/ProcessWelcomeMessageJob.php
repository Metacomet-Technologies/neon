<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WelcomeSetting;
use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessWelcomeMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userId,
        public string $guildId,
    ) {
        $this->userId = $userId;
        $this->guildId = $guildId;
    }

    public function handle(): void
    {
        $welcomeSetting = WelcomeSetting::where('guild_id', $this->guildId)->first();

        if ($welcomeSetting === null || ! $welcomeSetting->is_active) {
            return;
        }

        $channelId = $welcomeSetting->channel_id;
        $message = $welcomeSetting->message;

        try {
            $discord = app(DiscordService::class);
            $discord->channel($channelId)->send(str_replace('{user}', "<@{$this->userId}>", $message));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
