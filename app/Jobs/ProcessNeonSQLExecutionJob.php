<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessNeonSQLExecutionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $channelId,
        public string $discordUserId,
        public bool $userConfirmed
    ) {}

    public function handle(): void
    {
        $cacheKey = "neon_sql_{$this->channelId}_{$this->discordUserId}";
        $sqlCommands = Cache::get($cacheKey);

        if (!$sqlCommands) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '⏰ Neon AI - Expired',
                'embed_description' => 'The SQL command session has expired. Please run `!neon` with your query again.',
                'embed_color' => 15158332, // Red
            ]);
            return;
        }

        // Clear the cache immediately
        Cache::forget($cacheKey);

        if (!$this->userConfirmed) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Neon AI - Cancelled',
                'embed_description' => 'SQL execution cancelled by user.',
                'embed_color' => 15158332, // Red
            ]);
            return;
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
                            'count' => count($result)
                        ];
                        $executedCommands++;
                    } catch (Exception $e) {
                        $results[] = [
                            'command' => $sqlCommand,
                            'success' => false,
                            'error' => $e->getMessage()
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
                        'error' => 'Command rejected for safety reasons'
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

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Neon AI - Execution Error',
                'embed_description' => 'An error occurred while executing the SQL commands.',
                'embed_color' => 15158332, // Red
            ]);
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
            $description .= "**Command " . ($index + 1) . ":**\n";
            $description .= "`" . substr($result['command'], 0, 100) . (strlen($result['command']) > 100 ? '...' : '') . "`\n";

            if ($result['success']) {
                $description .= "✅ **Success** - Returned {$result['count']} rows\n";

                // Show first few results if any
                if (!empty($result['result']) && count($result['result']) > 0) {
                    $firstRow = (array) $result['result'][0];
                    $fields = array_slice(array_keys($firstRow), 0, 3); // Show first 3 fields
                    $description .= "📊 **Sample:** " . implode(', ', $fields) . "\n";
                }
            } else {
                $description .= "❌ **Error:** " . substr($result['error'], 0, 100) . "\n";
            }
            $description .= "\n";
        }

        // Discord has a 4096 character limit for embed descriptions
        if (strlen($description) > 4000) {
            $description = substr($description, 0, 3900) . "\n\n... (truncated)";
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🤖 Neon AI - Execution Results',
            'embed_description' => $description,
            'embed_color' => $executedCommands > 0 ? 3066993 : 15158332, // Green if successful, red if all failed
        ]);
    }
}
