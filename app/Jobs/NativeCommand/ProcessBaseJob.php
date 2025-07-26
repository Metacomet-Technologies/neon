<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\SendMessage;
use App\Services\CommandAnalyticsService;
use App\Services\DiscordApiService;
use App\Traits\DiscordPermissionTrait;
use App\Traits\DiscordResponseTrait;
use App\Traits\DiscordValidatorTrait;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;
use Throwable;

class ProcessBaseJob implements ShouldQueue
{
    use DiscordPermissionTrait;
    use DiscordResponseTrait;
    use DiscordValidatorTrait;
    use Queueable;

    public string $baseUrl;
    public string $discordUserId;
    public string $channelId;
    public string $guildId;
    public string $messageContent;
    public array $command;
    public string $commandSlug;
    public array $parameters;

    // Queue configuration for better failure handling
    public int $tries = 3;
    public int $maxExceptions = 1;
    public int $timeout = 30;

    protected DiscordApiService $discord;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        $this->discordUserId = $discordUserId;
        $this->channelId = $channelId;
        $this->guildId = $guildId;
        $this->messageContent = $messageContent;
        $this->command = $command;
        $this->commandSlug = $commandSlug;
        $this->parameters = $parameters;
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->discord = new DiscordApiService;
    }

    /**
     * Handles the job execution.
     */
    public function handle(CommandAnalyticsService $analytics): void
    {
        $startTime = microtime(true);

        try {
            // Execute the actual command logic in child classes
            $this->executeCommand();

            // Record successful execution
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $analytics->recordNativeCommandUsage(
                commandSlug: $this->commandSlug,
                guildId: $this->guildId,
                discordUserId: $this->discordUserId,
                parameters: $this->parameters,
                channelType: 'text', // Could be determined from Discord API
                executionTimeMs: $executionTime,
                status: 'success'
            );

        } catch (Exception $e) {
            // Record failed execution with error category
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $errorCategory = $this->categorizeError($e);

            $analytics->recordNativeCommandUsage(
                commandSlug: $this->commandSlug,
                guildId: $this->guildId,
                discordUserId: $this->discordUserId,
                parameters: $this->parameters,
                channelType: 'text',
                executionTimeMs: $executionTime,
                status: 'failed',
                errorCategory: $errorCategory
            );

            throw $e; // Re-throw to trigger queue retry logic
        }
    }

    /**
     * Handle job failure - Laravel will automatically add to failed_jobs table.
     */
    public function failed(Throwable $exception): void
    {
        // Optional: Additional cleanup or notification logic
        Log::error('Native command failed permanently', [
            'command' => $this->commandSlug,
            'guild_id' => $this->guildId,
            'user_id' => $this->discordUserId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Execute the specific command logic. Override in child classes.
     */
    protected function executeCommand(): void
    {
        // Default implementation - override in child classes
    }

    /**
     * Send usage and example information to Discord.
     */
    protected function sendUsageAndExample(?string $additionalInfo = null): void
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

    /**
     * Categorize errors for analytics.
     */
    private function categorizeError(Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'permission')) {
            return 'permissions';
        }
        if (str_contains($message, 'rate limit')) {
            return 'rate_limit';
        }
        if (str_contains($message, 'invalid') || str_contains($message, 'validation')) {
            return 'invalid_params';
        }
        if (str_contains($message, 'timeout')) {
            return 'timeout';
        }
        if (str_contains($message, 'network') || str_contains($message, 'connection')) {
            return 'network';
        }

        return 'unknown';
    }
}
