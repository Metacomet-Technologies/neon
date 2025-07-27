<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessNeonSQLExecutionJob extends ProcessBaseJob
{
    private readonly bool $userConfirmed;

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
        $this->userConfirmed = $parameters['user_confirmed'] ?? false;
    }

    protected function executeCommand(): void
    {
        $cacheKey = "neon_sql_{$this->channelId}_{$this->discordUserId}";
        $sqlCommands = Cache::get($cacheKey);

        if (! $sqlCommands) {
            $this->getDiscord()->channel($this->channelId)->sendEmbed(
                '⏰ Neon AI - Expired',
                'The SQL command session has expired. Please run `!neon` with your query again.',
                15158332 // Red
            );
            throw new Exception('Session expired.', 410);
        }

        // Clear the cache immediately
        Cache::forget($cacheKey);

        if (! $this->userConfirmed) {
            $this->getDiscord()->channel($this->channelId)->sendEmbed(
                '❌ Neon AI - Cancelled',
                'SQL execution cancelled by user.',
                15158332 // Red
            );
            throw new Exception('Cancelled by user.', 200);
        }

        try {
            $results = [];
            $executedCommands = 0;

            foreach ($sqlCommands as $sqlCommand) {
                if ($this->isSafeQuery($sqlCommand)) {
                    try {
                        $result = DB::select($sqlCommand);
                        $results[] = [
                            'command' => $sqlCommand,
                            'success' => true,
                            'result' => $result,
                            'count' => count($result),
                        ];
                        $executedCommands++;
                    } catch (Exception $e) {
                        $results[] = [
                            'command' => $sqlCommand,
                            'success' => false,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('SQL execution failed', [
                            'command' => $sqlCommand,
                            'error' => $e->getMessage(),
                            'user_id' => $this->discordUserId,
                        ]);
                    }
                } else {
                    $results[] = [
                        'command' => $sqlCommand,
                        'success' => false,
                        'error' => 'Command rejected for safety reasons',
                    ];
                }
            }

            $this->sendExecutionResults($results, $executedCommands);

        } catch (Exception $e) {
            Log::error('ProcessNeonSQLExecutionJob failed', [
                'error' => $e->getMessage(),
                'discord_user_id' => $this->discordUserId,
                'channel_id' => $this->channelId,
            ]);

            $this->sendErrorMessage('An error occurred while executing the SQL commands.');
        }
    }

    private function isSafeQuery(string $sql): bool
    {
        $sql = strtoupper(trim($sql));

        // Allow only SELECT, INSERT, UPDATE with WHERE clause
        $allowedPatterns = [
            '/^SELECT\s+/',
            '/^INSERT\s+INTO\s+/',
            '/^UPDATE\s+\w+\s+SET\s+.*\s+WHERE\s+/',
        ];

        // Dangerous patterns to block
        $dangerousPatterns = [
            '/DROP\s+/',
            '/TRUNCATE\s+/',
            '/DELETE\s+(?!.*WHERE)/',
            '/ALTER\s+/',
            '/CREATE\s+/',
            '/GRANT\s+/',
            '/REVOKE\s+/',
            '/EXEC\s+/',
            '/EXECUTE\s+/',
            '/xp_/',
            '/sp_/',
            '/UNION\s+/',
            '/;\s*--/',
            '/--/',
            '/\/\*/',
            '/\*\//',
        ];

        // Check for dangerous patterns
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return false;
            }
        }

        // Check if it matches allowed patterns
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    private function sendExecutionResults(array $results, int $executedCommands): void
    {
        $description = "**Executed {$executedCommands} out of " . count($results) . " commands**\n\n";

        foreach ($results as $index => $result) {
            $description .= '**Command ' . ($index + 1) . ":**\n";
            $description .= '`' . substr($result['command'], 0, 100) . (strlen($result['command']) > 100 ? '...' : '') . "`\n";

            if ($result['success']) {
                $description .= "✅ **Success** - Returned {$result['count']} rows\n";

                // Show first few results if any
                if (! empty($result['result']) && count($result['result']) > 0) {
                    $firstRow = (array) $result['result'][0];
                    $fields = array_slice(array_keys($firstRow), 0, 3); // Show first 3 fields
                    $description .= '📊 **Sample:** ' . implode(', ', $fields) . "\n";
                }
            } else {
                $description .= '❌ **Error:** ' . substr($result['error'], 0, 100) . "\n";
            }
            $description .= "\n";
        }

        // Discord has a 4096 character limit for embed descriptions
        if (strlen($description) > 4000) {
            $description = substr($description, 0, 3900) . "\n\n... (truncated)";
        }

        $this->getDiscord()->channel($this->channelId)->sendEmbed(
            '🤖 Neon AI - Execution Results',
            $description,
            $executedCommands > 0 ? 3066993 : 15158332 // Green if successful, red if all failed
        );
    }
}
