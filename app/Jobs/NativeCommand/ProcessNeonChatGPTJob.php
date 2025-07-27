<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\SendMessage;
use App\Services\CommandAnalyticsService;
use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

final class ProcessNeonChatGPTJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private string $userQuery = '';
    private array $dbSchema = [];
    private array $discordServerData = [];
    private int $retryDelay = 2000;
    private int $maxRetries = 3;

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
    }

    public function handle(CommandAnalyticsService $analytics): void
    {
        // Parse the user's query from the message
        $this->userQuery = $this->parseMessage($this->messageContent);

        // Validate that there's a query
        if (empty($this->userQuery)) {
            $this->sendUsageAndExample();
            $this->sendErrorMessage('No query provided.');

            return;
        }

        try {
            // Get database schema information
            $this->dbSchema = $this->getDatabaseSchema();

            // Get Discord server structure
            $this->discordServerData = $this->getDiscordServerStructure();

            // Send initial message to user
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🤖 Neon AI Assistant',
                'embed_description' => "🔍 **Analyzing your request:** \"{$this->userQuery}\"\n\n⏳ Please wait while I identify the appropriate Discord commands...",
                'embed_color' => 3066993, // Green
            ]);

            // Generate the ChatGPT response with database schema and Discord server context
            $chatGptResponse = $this->getChatGPTResponse();

            if (! $chatGptResponse) {
                $this->sendErrorMessage('Failed to get response from ChatGPT. Please try again.');

                return;
            }

            // Parse the response to extract Discord commands and synopsis
            $parsedResponse = $this->parseChatGPTResponse($chatGptResponse);

            if (! $parsedResponse) {
                $this->sendErrorMessage('Failed to parse ChatGPT response. Please try again.');

                return;
            }

            // Send synopsis and ask for confirmation
            $this->sendConfirmationMessage($parsedResponse);

            // Store the Discord commands in cache for potential execution
            $cacheKey = "neon_discord_{$this->channelId}_{$this->discordUserId}";
            $cacheData = [
                'discord_commands' => $parsedResponse['discord_commands'],
                'guild_id' => $this->guildId,
                'synopsis' => $parsedResponse['synopsis'],
            ];
            Cache::put($cacheKey, $cacheData, now()->addMinutes(5));

            Log::info('Neon ChatGPT request completed successfully', [
                'guild_id' => $this->guildId,
                'user_id' => $this->discordUserId,
            ]);

        } catch (Exception $e) {
            Log::error('ProcessNeonChatGPTJob failed', [
                'error' => $e->getMessage(),
                'user_query' => $this->userQuery,
                'discord_user_id' => $this->discordUserId,
                'channel_id' => $this->channelId,
            ]);

            $this->sendErrorMessage('An error occurred while processing your request. Please try again.');
            Log::error('Error in ProcessNeonChatGPTJob', [
                'error' => $e->getMessage(),
                'guild_id' => $this->guildId,
            ]);
            throw $e;
        }
    }

    protected function sendErrorMessage(string $message): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '❌ Neon AI - Error',
            'embed_description' => $message,
            'embed_color' => 15158332, // Red
        ]);

        Log::error('ChatGPT request failed', [
            'message' => $message,
            'guild_id' => $this->guildId,
        ]);
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
                        'columns' => array_map(function ($column) {
                            return [
                                'name' => $column->Field,
                                'type' => $column->Type,
                                'null' => $column->Null === 'YES',
                                'key' => $column->Key,
                                'default' => $column->Default,
                            ];
                        }, $columns),
                    ];
                }
            } catch (Exception $e) {
                Log::error('Failed to get database schema', ['error' => $e->getMessage()]);
            }

            return $schema;
        });
    }

    private function formatDiscordServerStructure(): string
    {
        if (empty($this->discordServerData)) {
            return 'CURRENT SERVER STRUCTURE: No server data available';
        }

        $structure = "CURRENT SERVER STRUCTURE:\n\n";

        // Format categories
        if (! empty($this->discordServerData['categories'])) {
            $structure .= "📁 CATEGORIES:\n";
            foreach ($this->discordServerData['categories'] as $category) {
                $structure .= "- {$category['name']} (ID: {$category['id']})\n";
            }
            $structure .= "\n";
        }

        // Format text channels
        if (! empty($this->discordServerData['text_channels'])) {
            $structure .= "💬 TEXT CHANNELS:\n";
            foreach ($this->discordServerData['text_channels'] as $channel) {
                $categoryInfo = '';
                if ($channel['category_id']) {
                    $category = collect($this->discordServerData['categories'])
                        ->firstWhere('id', $channel['category_id']);
                    if ($category) {
                        $categoryInfo = " (in {$category['name']})";
                    }
                }
                $structure .= "- {$channel['name']} (ID: {$channel['id']}){$categoryInfo}\n";
            }
            $structure .= "\n";
        }

        // Format voice channels
        if (! empty($this->discordServerData['voice_channels'])) {
            $structure .= "🔊 VOICE CHANNELS:\n";
            foreach ($this->discordServerData['voice_channels'] as $channel) {
                $categoryInfo = '';
                if ($channel['category_id']) {
                    $category = collect($this->discordServerData['categories'])
                        ->firstWhere('id', $channel['category_id']);
                    if ($category) {
                        $categoryInfo = " (in {$category['name']})";
                    }
                }
                $structure .= "- {$channel['name']} (ID: {$channel['id']}){$categoryInfo}\n";
            }
            $structure .= "\n";
        }

        $structure .= "IMPORTANT: When deleting channels or categories, use the exact names shown above.\n";
        $structure .= "For protected channels like 'welcome', 'general', or 'rules', be extra careful and confirm the user's intent.\n\n";

        return $structure;
    }

    private function getDiscordServerStructure(): array
    {
        // Cache Discord server structure for performance (5 minutes)
        $cacheKey = "discord_server_structure_{$this->guildId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            try {
                $discordService = app(DiscordService::class);
                $response = $discordService->get("/guilds/{$this->guildId}/channels");

                if ($response->failed()) {
                    Log::error('Failed to fetch Discord server structure', [
                        'status' => $response->status(),
                        'guild_id' => $this->guildId,
                    ]);

                    return [];
                }

                $channels = $response->json();
                $structure = [
                    'categories' => [],
                    'text_channels' => [],
                    'voice_channels' => [],
                ];

                foreach ($channels as $channel) {
                    switch ($channel['type']) {
                        case 4: // Category
                            $structure['categories'][] = [
                                'id' => $channel['id'],
                                'name' => $channel['name'],
                                'position' => $channel['position'] ?? 0,
                            ];
                            break;
                        case 0: // Text channel
                            $structure['text_channels'][] = [
                                'id' => $channel['id'],
                                'name' => $channel['name'],
                                'category_id' => $channel['parent_id'] ?? null,
                                'position' => $channel['position'] ?? 0,
                            ];
                            break;
                        case 2: // Voice channel
                            $structure['voice_channels'][] = [
                                'id' => $channel['id'],
                                'name' => $channel['name'],
                                'category_id' => $channel['parent_id'] ?? null,
                                'position' => $channel['position'] ?? 0,
                            ];
                            break;
                    }
                }

                // Sort by position
                usort($structure['categories'], fn ($a, $b) => $a['position'] <=> $b['position']);
                usort($structure['text_channels'], fn ($a, $b) => $a['position'] <=> $b['position']);
                usort($structure['voice_channels'], fn ($a, $b) => $a['position'] <=> $b['position']);

                return $structure;

            } catch (Exception $e) {
                Log::error('Exception while fetching Discord server structure', [
                    'error' => $e->getMessage(),
                    'guild_id' => $this->guildId,
                ]);

                return [];
            }
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
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.2,
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

    private function getAvailableDiscordCommands(): string
    {
        // Cache available commands for performance
        return Cache::remember('neon_available_commands', now()->addHours(1), function () {
            try {
                $commands = DB::table('native_commands')
                    ->where('is_active', true)
                    ->whereNotIn('slug', ['neon']) // Exclude the neon command itself
                    ->orderBy('slug')
                    ->get(['slug', 'usage', 'example', 'description']);

                $commandsText = "EXACT COMMAND SYNTAX (USE THESE EXACTLY):\n\n";

                // Group commands by category for better organization
                $categories = [
                    'Channel Management' => ['assign-channel', 'delete-channel', 'edit-channel-autohide', 'edit-channel-name', 'edit-channel-nsfw', 'edit-channel-slowmode', 'edit-channel-topic', 'lock-channel', 'new-channel', 'set-inactive', 'vanish', 'unvanish'],
                    'Category Management' => ['delete-category', 'new-category'],
                    'Role Management' => ['assign-role', 'delete-role', 'new-role', 'remove-role'],
                    'User Management' => ['ban', 'disconnect', 'kick', 'move-user', 'mute', 'prune', 'set-nickname', 'unban', 'unmute'],
                    'Voice Management' => ['lock-voice'],
                    'Message Management' => ['notify', 'pin', 'poll', 'purge', 'scheduled-message', 'unpin'],
                    'Event Management' => ['create-event', 'delete-event'],
                    'Server Management' => ['display-boost'],
                    'Utility' => ['color', 'help'],
                ];

                foreach ($categories as $categoryName => $categoryCommands) {
                    $commandsText .= "## {$categoryName}\n";

                    foreach ($commands as $command) {
                        if (in_array($command->slug, $categoryCommands)) {
                            $commandsText .= "**!{$command->slug}**\n";
                            $commandsText .= "  SYNTAX: {$command->usage}\n";
                            if ($command->example) {
                                $commandsText .= "  EXAMPLE: {$command->example}\n";
                            }
                            if ($command->description) {
                                $commandsText .= "  PURPOSE: {$command->description}\n";
                            }
                            $commandsText .= "\n";
                        }
                    }
                    $commandsText .= "\n";
                }

                return trim($commandsText);
            } catch (Exception $e) {
                Log::error('Failed to get available Discord commands', ['error' => $e->getMessage()]);

                return 'No commands available';
            }
        });
    }

    private function buildSystemPrompt(): string
    {
        $availableCommands = $this->getAvailableDiscordCommands();
        $serverStructure = $this->formatDiscordServerStructure();

        return "You are Neon, an AI assistant for a Discord bot that helps server administrators manage their Discord servers through natural language requests.

{$availableCommands}

{$serverStructure}

IMPORTANT: Handle TWO types of requests differently:

**TYPE 1: INFORMATIONAL REQUESTS** (list, show, what, how many, etc.)
For requests like \"list roles\", \"show channels\", \"what roles exist\", \"can you list roles for me\", respond with INFORMATION ONLY:
{
  \"synopsis\": \"Here are the current roles in your Discord server:\",
  \"discord_commands\": [],
  \"information_response\": \"**Current Roles:**\\n• Role1\\n• Role2\\n• Role3\"
}

IMPORTANT: For simple informational requests like \"list roles\", \"show me the roles\", \"what roles do we have\", ALWAYS use informational responses. DO NOT suggest the !list-roles command or any role assignment commands.

**TYPE 2: ACTION REQUESTS** (create, delete, modify, etc.)
For management requests like \"create channel\", \"delete role\", respond with COMMANDS:
{
  \"synopsis\": \"Brief explanation of what you plan to do\",
  \"discord_commands\": [
    \"!new-category newcomers\",
    \"!new-channel general-chat text newcomers\"
  ]
}

CRITICAL SYNTAX RULES:
1. Use ONLY the commands listed above - NEVER invent new commands
2. Follow the EXACT syntax shown in the SYNTAX line for each command
3. When deleting channels/categories, use the EXACT NAMES or IDs from the server structure above
4. Channel names must be Discord-compliant: lowercase, hyphens, underscores, NO SPACES or emojis
5. For boolean values, use exactly 'true' or 'false'
6. Always include the ! prefix for commands
7. Keep commands simple and independent
8. Use the actual channel and category names from the server structure
9. Focus on creating basic, working Discord structures
10. Use realistic examples that will actually work

IMPORTANT WORKFLOW RULES:
- Create categories first, then create channels in those categories
- Use simple category names (no spaces, no emojis)
- Use simple channel names (no spaces, no emojis)
- When creating channels in categories, use the category name as the third parameter
- Focus on functional setup over decorative features
- ALWAYS create channels directly in categories, don't use separate assign-channel commands
- When deleting, use exact names from the current server structure
- FOR CREATE-THEN-DELETE WORKFLOWS: Only provide creation commands and suggest user run deletion separately
- NEVER predict category/channel IDs that don't exist yet
- If user requests create+delete in one operation, suggest breaking it into two steps

COMMAND EXAMPLES:
✅ Good: !new-category newcomers
✅ Good: !new-channel general-chat text newcomers
✅ Good: !new-channel voice-lounge voice newcomers
✅ Good: !delete-category Gaming (uses exact name from server)
✅ Good: !delete-channel general (uses exact name from server)
❌ Bad: !new-channel 🌟Welcome-Text💬 text (emojis not allowed)
❌ Bad: !new-channel general-chat text (missing category reference)
❌ Bad: !assign-channel general-chat newcomers (avoid separate assignment)
❌ Bad: !delete-category category1 (use real names, not placeholders)
❌ Bad: !delete-category 1234567890123456789 (don't predict IDs that don't exist)

**CRITICAL: ALWAYS USE REAL DISCORD IDs FROM SERVER STRUCTURE!**
When the server structure shows channel/category IDs, use those exact IDs in commands:
✅ Good: !delete-channel 123456789012345678 (uses actual ID from server structure)
✅ Good: !edit-channel-slowmode 987654321098765432 5 (uses actual channel ID)
✅ Good: !lock-channel 456789012345678901 true (uses actual channel ID)
❌ Bad: !delete-channel <channel-id> (never use placeholder format)
❌ Bad: !edit-channel-slowmode <channel-id> 5 (never use placeholder format)

WORKFLOW PATTERNS:
1. Simple creation: !new-category category-name, !new-channel channel-name text category-name
2. Simple deletion: !delete-category exact-name-from-server
3. Create-then-delete: Suggest user creates first, then runs separate delete command
4. Complex operations: Break into logical, executable steps

SPECIAL HANDLING FOR CREATE+DELETE REQUESTS:
When user asks to \"create X and delete them\" or similar create-then-delete workflows:
- Respond with ONLY creation commands in this response
- Add clear note in synopsis: \"Creating items first. Please run a separate delete command afterward to clean up the created items.\"
- Explain why: \"This ensures we can identify the actual category/channel names and IDs after creation.\"
- Suggest follow-up: \"After creation completes, use '!neon delete all test categories' for cleanup.\"

OPTIMAL WORKFLOW PATTERN:
1. Create category: !new-category category-name
2. Create channels in that category: !new-channel channel-name text category-name
3. Create voice channels in that category: !new-channel voice-name voice category-name

RESPONSE FORMAT (JSON):
{
  \"synopsis\": \"Brief explanation of what you plan to do\",
  \"discord_commands\": [
    \"!new-category newcomers\",
    \"!new-channel general-chat text newcomers\",
    \"!new-channel voice-lounge voice newcomers\"
  ]
}

VALIDATION CHECKLIST:
- Every command starts with !
- Every command exists in the list above
- Syntax matches exactly (check parameter order, types, format)
- Channel names are simple, lowercase with hyphens (no spaces, no emojis)
- Boolean values are 'true' or 'false'
- Commands are independent and functional
- Workflow creates working Discord structures
- Deletion commands use exact names from server structure

Your role is to:
1. Understand Discord server management requests in natural language
2. Generate ONLY valid commands with correct syntax
3. Create simple, functional Discord structures that work reliably
4. Use the actual server structure data when deleting or modifying existing channels/categories

Focus on helping with:
- Basic channel and category creation
- Simple role and permission setup
- Server cleanup and organization using actual channel/category names
4. Provide practical solutions for Discord community building

Focus on helping with:
- Basic channel and category creation
- Simple role and permission setup
- Functional server organization
- Practical community building features";
    }

    private function buildUserPrompt(): string
    {
        return "User request: \"{$this->userQuery}\"

Please analyze this request and determine if it's:
1. **INFORMATIONAL** (asking for current information like \"list roles\", \"show channels\") → Provide information directly
2. **ACTION** (requesting changes like \"create channel\", \"delete role\") → Provide Discord commands

CRITICAL: Use ONLY the exact names from the CURRENT SERVER STRUCTURE above. Do NOT guess or assume names that aren't listed.

For informational requests: Respond with current server information from the structure above.
For action requests: Generate practical, working Discord commands using ONLY the resources shown in the server structure above.";
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
            Log::info('Parsing ChatGPT response', [
                'response_preview' => substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''),
                'response_length' => strlen($response),
            ]);

            // Try to extract JSON from markdown code blocks first
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonString = trim($matches[1]);
                Log::info('Found JSON in markdown code block');
            } else {
                // Fallback to looking for JSON brackets
                $jsonStart = strpos($response, '{');
                $jsonEnd = strrpos($response, '}');

                if ($jsonStart === false || $jsonEnd === false) {
                    Log::warning('No JSON found in ChatGPT response');

                    return null;
                }

                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                Log::info('Extracted JSON from brackets');
            }

            $parsed = json_decode($jsonString, true);

            if (! $parsed || ! isset($parsed['synopsis'])) {
                Log::warning('Invalid JSON structure - missing synopsis', [
                    'json_error' => json_last_error_msg(),
                ]);

                return null;
            }

            // Handle informational responses (no commands to execute)
            if (isset($parsed['information_response']) && (! isset($parsed['discord_commands']) || empty($parsed['discord_commands']))) {
                Log::info('Processing informational response');

                // This is an informational request - send the information directly
                $this->sendInformationalResponse($parsed['synopsis'], $parsed['information_response']);

                return null; // Don't proceed with command execution flow
            }

            // Handle command requests
            if (! isset($parsed['discord_commands'])) {
                Log::warning('No discord_commands found in parsed response');

                return null;
            }

            // Validate generated commands
            $validatedCommands = $this->validateDiscordCommands($parsed['discord_commands']);
            if (empty($validatedCommands)) {
                Log::warning('No valid commands found in ChatGPT response', [
                    'original_commands' => $parsed['discord_commands'],
                ]);

                return null;
            }

            Log::info('Successfully parsed ChatGPT response', [
                'command_count' => count($validatedCommands),
            ]);

            return [
                'discord_commands' => $validatedCommands,
                'synopsis' => $parsed['synopsis'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to parse ChatGPT response', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function validateDiscordCommands(array $commands): array
    {
        $availableCommands = DB::table('native_commands')
            ->where('is_active', true)
            ->whereNotIn('slug', ['neon'])
            ->pluck('slug')
            ->toArray();

        $validatedCommands = [];

        foreach ($commands as $command) {
            // Extract command name from the command string
            $commandParts = explode(' ', trim($command));
            if (empty($commandParts)) {
                continue;
            }

            $commandName = ltrim($commandParts[0], '!');

            // Check if command exists
            if (in_array($commandName, $availableCommands)) {
                $validatedCommands[] = $command;
            } else {
                Log::warning('Invalid command generated by ChatGPT', [
                    'command' => $command,
                    'extracted_name' => $commandName,
                    'available_commands' => $availableCommands,
                ]);
            }
        }

        return $validatedCommands;
    }

    private function sendConfirmationMessage(array $parsedResponse): void
    {
        $commandsText = '';
        foreach ($parsedResponse['discord_commands'] as $index => $command) {
            $commandsText .= ($index + 1) . ". `{$command}`\n";
        }

        // Send the confirmation message using DiscordApiService to get message ID
        $discordService = app(DiscordService::class);

        $embed = [
            'title' => '🤖 Neon AI - Action Plan',
            'description' => "**Synopsis:** {$parsedResponse['synopsis']}\n\n**Proposed Discord Commands:**\n{$commandsText}\n\n**Do you want me to execute these commands?**\nReact with ✅ for **Yes** or ❌ for **No**\n\n*Commands will expire in 5 minutes.*",
            'color' => 16776960, // Yellow for confirmation
            'footer' => [
                'text' => 'Sent from Neon',
            ],
        ];

        $response = $discordService->post("/channels/{$this->channelId}/messages", [
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
        $discordService = app(DiscordService::class);
        $encodedEmoji = urlencode($emoji);

        $discordService->put("/channels/{$this->channelId}/messages/{$messageId}/reactions/{$encodedEmoji}/@me");
    }

    private function sendInformationalResponse(string $synopsis, string $information): void
    {
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '📋 Neon AI - Server Information',
            'embed_description' => "**{$synopsis}**\n\n{$information}",
            'embed_color' => 3066993, // Green for informational responses
        ]);

        $this->updateNativeCommandRequestComplete();
    }
}
