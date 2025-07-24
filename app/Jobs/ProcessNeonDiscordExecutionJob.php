<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use App\Jobs\NeonDispatchHandler;
use App\Models\NativeCommand;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ProcessNeonDiscordExecutionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $channelId,
        public string $discordUserId,
        public string $guildId,
        public bool $userConfirmed
    ) {}

    public function handle(): void
    {
        $cacheKey = "neon_discord_{$this->channelId}_{$this->discordUserId}";
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '⏰ Neon AI - Expired',
                'embed_description' => 'The Discord command session has expired. Please run `!neon` with your request again.',
                'embed_color' => 15158332, // Red
            ]);
            return;
        }

        // Extract discord commands from cached data
        $discordCommands = $cachedData['discord_commands'] ?? $cachedData;

        // Clear the cache immediately
        Cache::forget($cacheKey);

        if (!$this->userConfirmed) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Neon AI - Cancelled',
                'embed_description' => 'Discord command execution cancelled by user.',
                'embed_color' => 15158332, // Red
            ]);
            return;
        }

        try {
            $results = [];
            $executedCommands = 0;
            $nativeCommands = $this->getNativeCommands();

            foreach ($discordCommands as $discordCommand) {
                $commandSlug = $this->extractCommandSlug($discordCommand);

                if ($this->isValidCommand($commandSlug, $nativeCommands)) {
                    try {
                        // Find the command definition
                        $command = $nativeCommands[$commandSlug];

                        // Execute the Discord command via NeonDispatchHandler
                        NeonDispatchHandler::dispatch(
                            $this->discordUserId,
                            $this->channelId,
                            $this->guildId,
                            $discordCommand,
                            $command
                        );

                        $results[] = [
                            'command' => $discordCommand,
                            'success' => true,
                            'status' => 'Dispatched successfully'
                        ];
                        $executedCommands++;

                    } catch (Exception $e) {
                        $results[] = [
                            'command' => $discordCommand,
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                        Log::error('Discord command execution failed', [
                            'command' => $discordCommand,
                            'error' => $e->getMessage(),
                            'user_id' => $this->discordUserId,
                        ]);
                    }
                } else {
                    $results[] = [
                        'command' => $discordCommand,
                        'success' => false,
                        'error' => 'Unknown or inactive command'
                    ];
                }
            }

            $this->sendExecutionResults($results, $executedCommands);

        } catch (Exception $e) {
            Log::error('ProcessNeonDiscordExecutionJob failed', [
                'error' => $e->getMessage(),
                'discord_user_id' => $this->discordUserId,
                'channel_id' => $this->channelId,
                'guild_id' => $this->guildId,
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Neon AI - Execution Error',
                'embed_description' => 'An error occurred while executing the Discord commands.',
                'embed_color' => 15158332, // Red
            ]);
        }
    }

    private function getNativeCommands(): array
    {
        $commands = NativeCommand::where('is_active', true)->get();
        $commandMap = [];

        foreach ($commands as $command) {
            $commandMap[$command->slug] = $command->toArray();
        }

        return $commandMap;
    }

    private function extractCommandSlug(string $discordCommand): string
    {
        // Extract command name from "!command-name args..."
        $parts = explode(' ', trim($discordCommand));
        $commandPart = $parts[0] ?? '';

        // Remove the ! prefix
        return ltrim($commandPart, '!');
    }

    private function isValidCommand(string $commandSlug, array $nativeCommands): bool
    {
        return isset($nativeCommands[$commandSlug]);
    }

    private function sendExecutionResults(array $results, int $executedCommands): void
    {
        $totalCommands = count($results);
        $successfulCommands = array_filter($results, fn($result) => $result['success']);
        $failedCommands = array_filter($results, fn($result) => !$result['success']);

        $description = "**Executed {$executedCommands} out of {$totalCommands} commands**\n\n";

        // Add successful commands
        if (!empty($successfulCommands)) {
            foreach ($successfulCommands as $index => $result) {
                $commandNumber = $index + 1;
                $description .= "**Command {$commandNumber}:**\n";
                $description .= "`{$result['command']}`\n";
                $description .= "✅ **Success** - {$result['status']}\n\n";
            }
        }

        // Add failed commands
        if (!empty($failedCommands)) {
            foreach ($failedCommands as $index => $result) {
                $commandNumber = count($successfulCommands) + $index + 1;
                $description .= "**Command {$commandNumber}:**\n";
                $description .= "`{$result['command']}`\n";
                $description .= "❌ **Failed** - {$result['error']}\n\n";
            }
        }

        $color = $executedCommands > 0 ? 3066993 : 15158332; // Green if any success, red if all failed

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🤖 Neon AI - Execution Results',
            'embed_description' => trim($description),
            'embed_color' => $color,
        ]);
    }
}
