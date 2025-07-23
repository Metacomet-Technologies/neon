<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

final class ProcessNeonChatGPTJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private string $userQuery = '';
    private array $dbSchema = [];
    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the user's query from the message
        $this->userQuery = $this->parseMessage($this->messageContent);

        // Validate that there's a query
        if (empty($this->userQuery)) {
            $this->sendUsageAndExample();
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No query provided.',
                statusCode: 400,
            );
            return;
        }

        try {
            // Get database schema information
            $this->dbSchema = $this->getDatabaseSchema();
            
            // Send initial message to user
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🤖 Neon AI Assistant',
                'embed_description' => "🔍 **Analyzing your request:** \"{$this->userQuery}\"\n\n⏳ Please wait while I identify the appropriate database operations...",
                'embed_color' => 3066993, // Green
            ]);

            // Generate the ChatGPT response with database schema context
            $chatGptResponse = $this->getChatGPTResponse();
            
            if (!$chatGptResponse) {
                $this->sendErrorMessage('Failed to get response from ChatGPT. Please try again.');
                return;
            }

            // Parse the response to extract SQL commands and synopsis
            $parsedResponse = $this->parseChatGPTResponse($chatGptResponse);
            
            if (!$parsedResponse) {
                $this->sendErrorMessage('Failed to parse ChatGPT response. Please try again.');
                return;
            }

            // Send synopsis and ask for confirmation
            $this->sendConfirmationMessage($parsedResponse);
            
            // Store the SQL commands in cache for potential execution
            $cacheKey = "neon_sql_{$this->channelId}_{$this->discordUserId}";
            Cache::put($cacheKey, $parsedResponse['sql_commands'], now()->addMinutes(5));
            
            $this->updateNativeCommandRequestComplete();

        } catch (Exception $e) {
            Log::error('ProcessNeonChatGPTJob failed', [
                'error' => $e->getMessage(),
                'user_query' => $this->userQuery,
                'discord_user_id' => $this->discordUserId,
                'channel_id' => $this->channelId,
            ]);

            $this->sendErrorMessage('An error occurred while processing your request. Please try again.');
            $this->updateNativeCommandRequestFailed(
                status: 'error',
                message: $e->getMessage(),
                statusCode: 500,
            );
        }
    }

    private function parseMessage(string $message): string
    {
        // Remove the !neon command and return the rest as the query
        return trim(str_replace('!neon', '', $message));
    }

    private function getDatabaseSchema(): array
    {
        // Cache database schema for performance
        return Cache::remember('neon_db_schema', now()->addHours(1), function () {
            $schema = [];
            
            try {
                // Get all table names
                $tables = DB::select('SHOW TABLES');
                $databaseName = DB::getDatabaseName();
                $tableColumn = "Tables_in_{$databaseName}";

                foreach ($tables as $table) {
                    $tableName = $table->$tableColumn;
                    
                    // Get column information for each table
                    $columns = DB::select("DESCRIBE {$tableName}");
                    
                    $schema[$tableName] = [
                        'columns' => array_map(function($column) {
                            return [
                                'name' => $column->Field,
                                'type' => $column->Type,
                                'null' => $column->Null === 'YES',
                                'key' => $column->Key,
                                'default' => $column->Default,
                            ];
                        }, $columns)
                    ];
                }
            } catch (Exception $e) {
                Log::error('Failed to get database schema', ['error' => $e->getMessage()]);
            }

            return $schema;
        });
    }

    private function getChatGPTResponse(): ?string
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt();

            $response = OpenAI::chat()->create([
                'model' => config('openai.model', 'gpt-3.5-turbo'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? null;

        } catch (Exception $e) {
            Log::error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'user_query' => $this->userQuery,
            ]);
            return null;
        }
    }

    private function buildSystemPrompt(): string
    {
        $schemaText = $this->formatSchemaForPrompt();
        
        return "You are Neon, an AI assistant for a Discord bot application that helps users interact with their database through natural language queries.

DATABASE SCHEMA:
{$schemaText}

Your role is to:
1. Analyze user requests and convert them into appropriate SQL queries
2. Provide a clear synopsis of what you plan to do
3. Return ONLY valid SQL commands that are safe to execute

RESPONSE FORMAT (JSON):
{
  \"synopsis\": \"Brief explanation of what you plan to do\",
  \"sql_commands\": [
    \"SELECT * FROM users WHERE...\",
    \"UPDATE table SET...\"
  ]
}

SAFETY RULES:
- NEVER use DROP, TRUNCATE, or DELETE without WHERE clauses
- Only SELECT, INSERT, UPDATE with proper WHERE clauses
- No system commands or schema modifications
- Validate all inputs and use proper escaping
- If the request is unclear or dangerous, explain why it cannot be fulfilled

Focus on common Discord bot operations like:
- User management queries
- Server/guild information
- Command usage statistics
- Role and permission queries
- Message and channel data";
    }

    private function buildUserPrompt(): string
    {
        return "User request: \"{$this->userQuery}\"

Please analyze this request and provide appropriate SQL commands to fulfill it. Remember to be safe and only suggest queries that are appropriate for a Discord bot database.";
    }

    private function formatSchemaForPrompt(): string
    {
        $schemaText = '';
        
        foreach ($this->dbSchema as $tableName => $tableInfo) {
            $schemaText .= "\nTable: {$tableName}\n";
            foreach ($tableInfo['columns'] as $column) {
                $schemaText .= "  - {$column['name']} ({$column['type']})";
                if ($column['key'] === 'PRI') {
                    $schemaText .= ' [PRIMARY KEY]';
                }
                $schemaText .= "\n";
            }
        }

        return $schemaText;
    }

    private function parseChatGPTResponse(string $response): ?array
    {
        try {
            // Try to extract JSON from the response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                return null;
            }
            
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);
            
            if (!$parsed || !isset($parsed['synopsis']) || !isset($parsed['sql_commands'])) {
                return null;
            }

            return $parsed;

        } catch (Exception $e) {
            Log::error('Failed to parse ChatGPT response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);
            return null;
        }
    }

    private function sendConfirmationMessage(array $parsedResponse): void
    {
        $sqlCommandsText = '';
        foreach ($parsedResponse['sql_commands'] as $index => $command) {
            $sqlCommandsText .= ($index + 1) . ". `{$command}`\n";
        }

        // Send the confirmation message using HTTP client to get message ID
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/channels/' . $this->channelId . '/messages';

        $embed = [
            'title' => '🤖 Neon AI - Action Plan',
            'description' => "**Synopsis:** {$parsedResponse['synopsis']}\n\n**Proposed SQL Commands:**\n{$sqlCommandsText}\n\n**Do you want me to execute these commands?**\nReact with ✅ for **Yes** or ❌ for **No**\n\n*Commands will expire in 5 minutes.*",
            'color' => 16776960, // Yellow for confirmation
            'footer' => [
                'text' => 'Sent from Neon',
            ],
        ];

        $response = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'embeds' => [$embed],
            ]);

        if ($response->successful()) {
            $messageData = $response->json();
            $messageId = $messageData['id'];

            // Add reactions to the message
            $this->addReactionToMessage($messageId, '✅');
            $this->addReactionToMessage($messageId, '❌');
        } else {
            Log::error('Failed to send confirmation message', [
                'channel_id' => $this->channelId,
                'response' => $response->json(),
            ]);
        }
    }

    private function addReactionToMessage(string $messageId, string $emoji): void
    {
        $baseUrl = config('services.discord.rest_api_url');
        $encodedEmoji = urlencode($emoji);
        $url = "{$baseUrl}/channels/{$this->channelId}/messages/{$messageId}/reactions/{$encodedEmoji}/@me";

        Http::withToken(config('discord.token'), 'Bot')->put($url);
    }

    private function sendErrorMessage(string $message): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '❌ Neon AI - Error',
            'embed_description' => $message,
            'embed_color' => 15158332, // Red
        ]);
        
        $this->updateNativeCommandRequestFailed(
            status: 'error',
            message: $message,
            statusCode: 500,
        );
    }
}
