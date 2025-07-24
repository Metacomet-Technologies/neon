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

        // For bulk operations, extend cache to prevent expiration during execution
        $commandCount = count($cachedData['discord_commands'] ?? $cachedData);
        if ($commandCount > 5) {
            $extendedTime = now()->addMinutes(15); // 15 minutes for bulk operations
            Cache::put($cacheKey, $cachedData, $extendedTime);
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

            // Detect bulk operations and warn user about timing
            $destructiveCommands = [
                'delete-category', 'delete-channel', 'delete-event', 'delete-role',
                'ban', 'kick', 'mute', 'disconnect', 'purge', 'prune',
                'remove-role', 'vanish', 'unpin'
            ];
            
            $destructiveCommandCount = count(array_filter($discordCommands, function($cmd) use ($destructiveCommands) {
                $commandSlug = $this->extractCommandSlug($cmd);
                return in_array($commandSlug, $destructiveCommands);
            }));

            if ($destructiveCommandCount > 5) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => true,
                    'embed_title' => '⚡ Bulk Operation Detected',
                    'embed_description' => "Executing {$destructiveCommandCount} destructive commands in optimized order for faster completion. This should complete within 30-60 seconds.",
                    'embed_color' => 16776960, // Yellow
                ]);

                // Extend cache duration for bulk operations to prevent session expiration
                $cacheKey = "neon_ai_session_{$this->discordUserId}_{$this->channelId}";
                $existingData = Cache::get($cacheKey);
                if ($existingData) {
                    Cache::put($cacheKey, $existingData, now()->addMinutes(15)); // Extend to 15 minutes
                    Log::info('Extended cache duration for bulk operation', [
                        'destructive_count' => $destructiveCommandCount,
                        'user_id' => $this->discordUserId,
                        'cache_key' => $cacheKey
                    ]);
                }
            }

            // Separate commands into proper execution categories to prevent dependency conflicts
            $commandCategories = $this->categorizeCommands($discordCommands);
            
            // Log the categorization for monitoring
            Log::info('Command categorization for execution', [
                'recovery_count' => count($commandCategories['recovery']),
                'constructive_count' => count($commandCategories['constructive']),
                'modification_count' => count($commandCategories['modification']),
                'destructive_count' => count($commandCategories['destructive']),
                'total_commands' => count($discordCommands)
            ]);

            // Execute commands in dependency-safe order
            // 1. RECOVERY commands first (unban, unmute, unvanish) - restore access
            foreach ($commandCategories['recovery'] as $commandData) {
                $this->executeCommand($commandData['command'], $commandData['index'], $nativeCommands, $results, $executedCommands);
            }

            // 2. CONSTRUCTIVE commands second (create, assign, add) - build dependencies
            foreach ($commandCategories['constructive'] as $commandData) {
                $this->executeCommand($commandData['command'], $commandData['index'], $nativeCommands, $results, $executedCommands);
            }

            // 3. MODIFICATION commands third (edit, lock, move) - modify existing resources
            if (!empty($commandCategories['modification'])) {
                $this->executeModificationCommandsInParallel($commandCategories['modification'], $nativeCommands, $results, $executedCommands);
            }

            // 4. DESTRUCTIVE commands last (delete, remove, ban) - cleanup phase
            if (!empty($commandCategories['destructive'])) {
                $this->executeDestructiveCommandsInParallel($commandCategories['destructive'], $nativeCommands, $results, $executedCommands);
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

    private function executeCommand(string $discordCommand, int $index, array $nativeCommands, array &$results, int &$executedCommands): void
    {
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

                // Add delay for non-delete commands
                if ($commandSlug === 'new-category') {
                    sleep(2); // Wait for category creation
                } elseif ($commandSlug === 'new-channel') {
                    sleep(1); // Wait for channel creation
                }

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

    /**
     * Categorize commands by their impact to ensure safe execution order
     */
    private function categorizeCommands(array $discordCommands): array
    {
        // Define command categories based on their impact and dependencies
        $destructiveCommands = [
            'delete-category', 'delete-channel', 'delete-event', 'delete-role',
            'ban', 'kick', 'mute', 'disconnect', 'purge', 'prune',
            'remove-role', 'vanish', 'unpin'
        ];

        $constructiveCommands = [
            'new-category', 'new-channel', 'new-role', 'create-event',
            'assign-role', 'assign-channel', 'pin', 'notify', 'poll',
            'scheduled-message'
        ];

        $modificationCommands = [
            'edit-channel-autohide', 'edit-channel-name', 'edit-channel-nsfw',
            'edit-channel-slowmode', 'edit-channel-topic', 'lock-channel', 
            'lock-voice', 'move-user', 'set-inactive', 'set-nickname',
            'display-boost'
        ];

        $recoveryCommands = [
            'unban', 'unmute', 'unvanish'
        ];

        $categories = [
            'recovery' => [],
            'constructive' => [],
            'modification' => [],
            'destructive' => []
        ];

        foreach ($discordCommands as $index => $discordCommand) {
            $commandSlug = $this->extractCommandSlug($discordCommand);
            $commandData = ['command' => $discordCommand, 'index' => $index];

            if (in_array($commandSlug, $recoveryCommands)) {
                $categories['recovery'][] = $commandData;
            } elseif (in_array($commandSlug, $constructiveCommands)) {
                $categories['constructive'][] = $commandData;
            } elseif (in_array($commandSlug, $modificationCommands)) {
                $categories['modification'][] = $commandData;
            } elseif (in_array($commandSlug, $destructiveCommands)) {
                $categories['destructive'][] = $commandData;
            } else {
                // Unknown commands go to modification category as safest option
                Log::warning('Unknown command category, defaulting to modification', [
                    'command' => $discordCommand,
                    'slug' => $commandSlug
                ]);
                $categories['modification'][] = $commandData;
            }
        }

        return $categories;
    }

    /**
     * Execute modification commands in parallel batches (safe for parallelization)
     */
    private function executeModificationCommandsInParallel(array $modificationCommands, array $nativeCommands, array &$results, int &$executedCommands): void
    {
        $batchSize = 3; // Same batch size as destructive commands
        $batches = array_chunk($modificationCommands, $batchSize);

        Log::info('Starting parallel modification command execution', [
            'total_commands' => count($modificationCommands),
            'batches' => count($batches),
            'batch_size' => $batchSize
        ]);

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $commandIndex => $commandData) {
                $this->executeCommandWithDelay($commandData['command'], $commandData['index'], $nativeCommands, $results, $executedCommands, $batchIndex, $commandIndex);

                if ($commandIndex < count($batch) - 1) {
                    sleep(1); // 1 second stagger within batch
                }
            }

            if ($batchIndex < count($batches) - 1) {
                sleep(2); // 2 seconds between batches
            }
        }
    }

    /**
     * Execute destructive commands in parallel batches (renamed from executeDeleteCommandsInParallel)
     */
    private function executeDestructiveCommandsInParallel(array $destructiveCommands, array $nativeCommands, array &$results, int &$executedCommands): void
    {
        // Execute destructive commands with optimized rate limiting based on testing results
        $batchSize = 3; // Increased to 3 for better throughput while maintaining API compliance
        $batches = array_chunk($destructiveCommands, $batchSize);

        Log::info('Starting parallel destructive command execution', [
            'total_commands' => count($destructiveCommands),
            'batches' => count($batches),
            'batch_size' => $batchSize,
            'estimated_time' => count($batches) * 2 . ' seconds dispatch time'
        ]);

        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing batch " . ($batchIndex + 1) . "/" . count($batches), [
                'commands_in_batch' => count($batch)
            ]);

            // Execute batch with staggered delays to prevent API rate limiting
            foreach ($batch as $commandIndex => $commandData) {
                $this->executeCommandWithDelay($commandData['command'], $commandData['index'], $nativeCommands, $results, $executedCommands, $batchIndex, $commandIndex);

                // Maintain 1 second stagger within batch (working well)
                if ($commandIndex < count($batch) - 1) {
                    sleep(1); // 1 second stagger within batch
                }
            }

            // Optimized delay between batches for better throughput
            if ($batchIndex < count($batches) - 1) {
                sleep(2); // Reduced to 2 seconds between batches for faster completion
                Log::info("Waiting 2 seconds before next batch to respect Discord API limits");
            }
        }

        Log::info('Completed parallel destructive command execution', [
            'total_executed' => $executedCommands
        ]);
    }

    private function executeCommandWithDelay(string $discordCommand, int $commandIndex, array $nativeCommands, array &$results, int &$executedCommands, int $batchIndex, int $commandInBatch): void
    {
        $commandSlug = $this->extractCommandSlug($discordCommand);

        if (isset($nativeCommands[$commandSlug])) {
            try {
                $command = $nativeCommands[$commandSlug];

                // Check circuit breaker for this guild
                $failureKey = "api_failures_guild_{$this->guildId}";
                $failureCount = Cache::get($failureKey, 0);

                if ($failureCount >= 3) {
                    $results[] = [
                        'command' => $discordCommand,
                        'success' => false,
                        'error' => 'API temporarily unavailable (circuit breaker active)'
                    ];
                    Log::warning('Command skipped due to circuit breaker', [
                        'command' => $discordCommand,
                        'failure_count' => $failureCount
                    ]);
                    return;
                }

                // Add execution delay based on position to prevent rate limiting
                // More conservative timing: (batch * 4) + (position * 1.5) for better API compliance
                $executionDelay = ($batchIndex * 4) + ($commandInBatch * 2); // Increased spacing for API safety

                Log::info('Dispatching delete command with delay', [
                    'command' => $discordCommand,
                    'batch' => $batchIndex + 1,
                    'position_in_batch' => $commandInBatch + 1,
                    'execution_delay' => $executionDelay . 's'
                ]);

                // Execute the Discord command via NeonDispatchHandler with delay
                NeonDispatchHandler::dispatch(
                    $this->discordUserId,
                    $this->channelId,
                    $this->guildId,
                    $discordCommand,
                    $command
                )->delay(now()->addSeconds($executionDelay));

                $executedCommands++;
                $results[] = [
                    'command' => $discordCommand,
                    'success' => true,
                    'status' => "Scheduled for execution in {$executionDelay} seconds"
                ];

            } catch (Exception $e) {
                // Increment failure count for circuit breaker
                $failureKey = "api_failures_guild_{$this->guildId}";
                $currentFailures = Cache::get($failureKey, 0);
                Cache::put($failureKey, $currentFailures + 1, now()->addMinutes(5));

                $results[] = [
                    'command' => $discordCommand,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                Log::error('Discord command execution failed', [
                    'command' => $discordCommand,
                    'error' => $e->getMessage(),
                    'user_id' => $this->discordUserId,
                    'batch' => $batchIndex + 1,
                    'position_in_batch' => $commandInBatch + 1
                ]);
            }
        } else {
            $results[] = [
                'command' => $discordCommand,
                'success' => false,
                'error' => 'Unknown or inactive command'
            ];
            Log::warning('Unknown command in delete batch', [
                'command' => $discordCommand,
                'command_slug' => $commandSlug
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
