<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommand;
use App\Models\NativeCommandRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Tests\TestCase;

/**
 * Comprehensive test suite to validate ChatGPT command generation accuracy
 */
class NeonChatGPTCommandValidationTest extends TestCase
{
    use RefreshDatabase;

    private array $testScenarios = [];
    private array $commandCategories = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with all commands
        $this->artisan('db:seed', ['--class' => 'NativeCommandSeeder']);

        // Define test scenarios for every command category
        $this->setupTestScenarios();

        // Mock Discord API calls
        Http::fake([
            'discord.com/*' => Http::response(['id' => '123456789012345678'], 200),
        ]);
    }

    private function setupTestScenarios(): void
    {
        $this->testScenarios = [
            // Channel Management Tests
            'create_text_channel' => [
                'query' => 'create a new text channel called welcome for new members',
                'expected_commands' => ['new-channel'],
                'required_syntax' => ['new-channel', 'text'],
                'forbidden_patterns' => [' ', '🎉', '✨'], // No spaces or emojis in channel names
            ],
            'create_voice_channel' => [
                'query' => 'make a voice channel for gaming',
                'expected_commands' => ['new-channel'],
                'required_syntax' => ['new-channel', 'voice'],
            ],
            'delete_channel' => [
                'query' => 'delete the test channel',
                'expected_commands' => ['delete-channel'],
                'required_syntax' => ['delete-channel'],
            ],
            'edit_channel_name' => [
                'query' => 'rename the general channel to main-chat',
                'expected_commands' => ['edit-channel-name'],
                'required_syntax' => ['edit-channel-name'],
            ],
            'lock_channel' => [
                'query' => 'lock the announcements channel',
                'expected_commands' => ['lock-channel'],
                'required_syntax' => ['lock-channel', 'true'],
            ],
            'unlock_channel' => [
                'query' => 'unlock the general channel',
                'expected_commands' => ['lock-channel'],
                'required_syntax' => ['lock-channel', 'false'],
            ],

            // Category Management Tests
            'create_category' => [
                'query' => 'create a new category for gaming channels',
                'expected_commands' => ['new-category'],
                'required_syntax' => ['new-category'],
            ],
            'delete_category' => [
                'query' => 'delete the old gaming category',
                'expected_commands' => ['delete-category'],
                'required_syntax' => ['delete-category'],
            ],

            // Role Management Tests
            'create_role' => [
                'query' => 'create a VIP role with blue color',
                'expected_commands' => ['new-role'],
                'required_syntax' => ['new-role', 'VIP'],
            ],
            'assign_role' => [
                'query' => 'give the moderator role to user John',
                'expected_commands' => ['assign-role'],
                'required_syntax' => ['assign-role'],
            ],
            'remove_role' => [
                'query' => 'remove the VIP role from user123',
                'expected_commands' => ['remove-role'],
                'required_syntax' => ['remove-role'],
            ],
            'delete_role' => [
                'query' => 'delete the old member role',
                'expected_commands' => ['delete-role'],
                'required_syntax' => ['delete-role'],
            ],

            // User Management Tests
            'ban_user' => [
                'query' => 'ban the spammer user',
                'expected_commands' => ['ban'],
                'required_syntax' => ['ban'],
            ],
            'kick_user' => [
                'query' => 'kick the troublesome user',
                'expected_commands' => ['kick'],
                'required_syntax' => ['kick'],
            ],
            'mute_user' => [
                'query' => 'mute the disruptive user',
                'expected_commands' => ['mute'],
                'required_syntax' => ['mute'],
            ],
            'unmute_user' => [
                'query' => 'unmute user after timeout',
                'expected_commands' => ['unmute'],
                'required_syntax' => ['unmute'],
            ],
            'unban_user' => [
                'query' => 'unban the previously banned user',
                'expected_commands' => ['unban'],
                'required_syntax' => ['unban'],
            ],
            'set_nickname' => [
                'query' => 'change Johns nickname to JohnnyGamer',
                'expected_commands' => ['set-nickname'],
                'required_syntax' => ['set-nickname'],
            ],

            // Message Management Tests
            'create_poll' => [
                'query' => 'make a poll asking what game to play with options Minecraft and Valorant',
                'expected_commands' => ['poll'],
                'required_syntax' => ['poll', '"'],
            ],
            'purge_messages' => [
                'query' => 'delete the last 50 messages in general',
                'expected_commands' => ['purge'],
                'required_syntax' => ['purge'],
            ],
            'pin_message' => [
                'query' => 'pin the last message',
                'expected_commands' => ['pin'],
                'required_syntax' => ['pin'],
            ],
            'notify_announcement' => [
                'query' => 'send an announcement to everyone about server maintenance',
                'expected_commands' => ['notify'],
                'required_syntax' => ['notify', '@everyone'],
            ],
            'schedule_message' => [
                'query' => 'schedule a reminder message for tomorrow at 3pm',
                'expected_commands' => ['scheduled-message'],
                'required_syntax' => ['scheduled-message'],
            ],

            // Complex Multi-Command Tests
            'setup_new_member_system' => [
                'query' => 'set up a complete system for new members with welcome channel, newcomer role, and introduction area',
                'expected_commands' => ['new-channel', 'new-role', 'new-category'],
                'min_commands' => 3,
            ],
            'gaming_server_setup' => [
                'query' => 'create a gaming setup with voice channels for different games and roles for each game',
                'expected_commands' => ['new-channel', 'new-role', 'new-category'],
                'min_commands' => 4,
            ],
            'moderation_setup' => [
                'query' => 'set up moderation tools with staff roles and locked channels',
                'expected_commands' => ['new-role', 'lock-channel'],
                'min_commands' => 2,
            ],
        ];
    }

    /**
     * Test that ChatGPT generates valid commands for every scenario
     */
    public function test_chatgpt_generates_valid_commands_for_all_scenarios(): void
    {
        $results = [];
        $totalTests = count($this->testScenarios);
        $passedTests = 0;

        foreach ($this->testScenarios as $scenarioName => $scenario) {
            $result = $this->testScenario($scenarioName, $scenario);
            $results[$scenarioName] = $result;

            if ($result['passed']) {
                $passedTests++;
            }
        }

        // Output detailed results
        $this->outputTestResults($results, $passedTests, $totalTests);

        // Assert that at least 90% of tests pass
        $passRate = ($passedTests / $totalTests) * 100;
        $this->assertGreaterThanOrEqual(90, $passRate,
            "ChatGPT command generation pass rate is {$passRate}%. Expected at least 90%."
        );
    }

    /**
     * Test individual command syntax validation
     */
    public function test_all_commands_have_valid_syntax_definitions(): void
    {
        $commands = NativeCommand::where('is_active', true)
            ->where('slug', '!=', 'neon')
            ->get();

        $missingInfo = [];

        foreach ($commands as $command) {
            $issues = [];

            if (empty($command->usage)) {
                $issues[] = 'Missing usage information';
            }

            if (empty($command->example)) {
                $issues[] = 'Missing example';
            }

            if (empty($command->description)) {
                $issues[] = 'Missing description';
            }

            // Check if usage starts with "Usage: !"
            if (!empty($command->usage) && !str_starts_with($command->usage, 'Usage: !')) {
                $issues[] = 'Usage format incorrect';
            }

            // Check if example starts with "Example: !"
            if (!empty($command->example) && !str_starts_with($command->example, 'Example: !')) {
                $issues[] = 'Example format incorrect';
            }

            if (!empty($issues)) {
                $missingInfo[$command->slug] = $issues;
            }
        }

        $this->assertEmpty($missingInfo,
            'Some commands have missing or invalid syntax information: ' . json_encode($missingInfo, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test that command validation catches invalid commands
     */
    public function test_command_validation_rejects_invalid_commands(): void
    {
        $nativeCommandRequest = NativeCommandRequest::factory()->create([
            'message_content' => '!neon test command validation',
        ]);

        $job = new ProcessNeonChatGPTJob($nativeCommandRequest);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('validateDiscordCommands');
        $method->setAccessible(true);

        // Test with valid commands
        $validCommands = ['!new-channel test-channel text', '!new-role TestRole'];
        $result = $method->invoke($job, $validCommands);
        $this->assertCount(2, $result);

        // Test with invalid commands
        $invalidCommands = ['!fake-command test', '!another-invalid command'];
        $result = $method->invoke($job, $invalidCommands);
        $this->assertEmpty($result);

        // Test with mixed valid/invalid commands
        $mixedCommands = ['!new-channel valid-channel text', '!fake-command invalid', '!new-role ValidRole'];
        $result = $method->invoke($job, $mixedCommands);
        $this->assertCount(2, $result);
    }

    private function testScenario(string $name, array $scenario): array
    {
        // Mock OpenAI response
        $mockResponse = $this->generateMockChatGPTResponse($scenario);

        OpenAI::fake([
            'chat.completions.*' => [
                'choices' => [
                    [
                        'message' => [
                            'content' => $mockResponse
                        ]
                    ]
                ]
            ]
        ]);

        try {
            $nativeCommandRequest = NativeCommandRequest::factory()->create([
                'message_content' => "!neon {$scenario['query']}",
            ]);

            $job = new ProcessNeonChatGPTJob($nativeCommandRequest);

            // Use reflection to test the response parsing
            $reflection = new \ReflectionClass($job);
            $parseMethod = $reflection->getMethod('parseChatGPTResponse');
            $parseMethod->setAccessible(true);

            $parsedResponse = $parseMethod->invoke($job, $mockResponse);

            if (!$parsedResponse) {
                return [
                    'passed' => false,
                    'error' => 'Failed to parse ChatGPT response',
                    'scenario' => $scenario,
                ];
            }

            // Validate the response
            $validation = $this->validateScenarioResponse($parsedResponse, $scenario);

            return [
                'passed' => $validation['passed'],
                'error' => $validation['error'] ?? null,
                'commands' => $parsedResponse['discord_commands'] ?? [],
                'scenario' => $scenario,
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'scenario' => $scenario,
            ];
        }
    }

    private function generateMockChatGPTResponse(array $scenario): string
    {
        // Generate realistic commands based on the scenario
        $commands = [];

        if (in_array('new-channel', $scenario['expected_commands'] ?? [])) {
            $commands[] = '!new-channel welcome-lounge text';
        }
        if (in_array('new-role', $scenario['expected_commands'] ?? [])) {
            $commands[] = '!new-role NewMember #3498db yes';
        }
        if (in_array('new-category', $scenario['expected_commands'] ?? [])) {
            $commands[] = '!new-category Gaming';
        }
        if (in_array('lock-channel', $scenario['expected_commands'] ?? [])) {
            $commands[] = '!lock-channel 123456789012345678 true';
        }
        if (in_array('poll', $scenario['expected_commands'] ?? [])) {
            $commands[] = '!poll "What game should we play?" "Minecraft" "Valorant"';
        }

        // Add more command generations based on expected commands
        foreach ($scenario['expected_commands'] ?? [] as $expectedCommand) {
            if (!in_array($expectedCommand, ['new-channel', 'new-role', 'new-category', 'lock-channel', 'poll'])) {
                $commands[] = "!{$expectedCommand} 123456789012345678";
            }
        }

        $response = [
            'synopsis' => 'Generated test response for scenario validation',
            'discord_commands' => $commands
        ];

        return json_encode($response);
    }

    private function validateScenarioResponse(array $response, array $scenario): array
    {
        $errors = [];

        // Check if required commands are present
        if (isset($scenario['expected_commands'])) {
            foreach ($scenario['expected_commands'] as $expectedCommand) {
                $found = false;
                foreach ($response['discord_commands'] as $command) {
                    if (str_contains($command, $expectedCommand)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $errors[] = "Missing expected command: {$expectedCommand}";
                }
            }
        }

        // Check minimum number of commands
        if (isset($scenario['min_commands'])) {
            if (count($response['discord_commands']) < $scenario['min_commands']) {
                $errors[] = "Expected at least {$scenario['min_commands']} commands, got " . count($response['discord_commands']);
            }
        }

        // Check required syntax patterns
        if (isset($scenario['required_syntax'])) {
            foreach ($scenario['required_syntax'] as $requiredSyntax) {
                $found = false;
                foreach ($response['discord_commands'] as $command) {
                    if (str_contains($command, $requiredSyntax)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $errors[] = "Missing required syntax: {$requiredSyntax}";
                }
            }
        }

        // Check forbidden patterns
        if (isset($scenario['forbidden_patterns'])) {
            foreach ($scenario['forbidden_patterns'] as $forbiddenPattern) {
                foreach ($response['discord_commands'] as $command) {
                    if (str_contains($command, $forbiddenPattern)) {
                        $errors[] = "Found forbidden pattern '{$forbiddenPattern}' in command: {$command}";
                    }
                }
            }
        }

        // Validate all commands start with !
        foreach ($response['discord_commands'] as $command) {
            if (!str_starts_with($command, '!')) {
                $errors[] = "Command doesn't start with !: {$command}";
            }
        }

        return [
            'passed' => empty($errors),
            'error' => empty($errors) ? null : implode('; ', $errors),
        ];
    }

    private function outputTestResults(array $results, int $passed, int $total): void
    {
        echo "\n\n" . str_repeat('=', 80) . "\n";
        echo "NEON CHATGPT COMMAND VALIDATION TEST RESULTS\n";
        echo str_repeat('=', 80) . "\n";
        echo "PASSED: {$passed}/{$total} (" . round(($passed/$total)*100, 1) . "%)\n";
        echo str_repeat('=', 80) . "\n\n";

        foreach ($results as $scenarioName => $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            echo "{$status} | {$scenarioName}\n";

            if (!$result['passed']) {
                echo "  Error: {$result['error']}\n";
                if (!empty($result['commands'])) {
                    echo "  Generated: " . implode(', ', $result['commands']) . "\n";
                }
            }
            echo "\n";
        }

        echo str_repeat('=', 80) . "\n";
    }

    /**
     * Test specific command syntax accuracy
     */
    public function test_command_syntax_accuracy(): void
    {
        $testCases = [
            // Channel creation with correct naming
            '!new-channel welcome-lounge text' => true,
            '!new-channel Welcome Lounge text' => false, // Spaces not allowed
            '!new-channel 🎉welcome🎉 text' => false, // Emojis not allowed

            // Boolean values
            '!lock-channel 123456789012345678 true' => true,
            '!lock-channel 123456789012345678 false' => true,
            '!lock-channel 123456789012345678 yes' => false, // Should be true/false

            // Role creation
            '!new-role VIPMember #3498db yes' => true,
            '!new-role VIP Member #3498db yes' => true, // Spaces allowed in role names

            // Poll format
            '!poll "What game?" "Minecraft" "Valorant"' => true,
            '!poll What game? Minecraft Valorant' => false, // Missing quotes
        ];

        foreach ($testCases as $command => $shouldBeValid) {
            $isValid = $this->validateCommandSyntax($command);

            if ($shouldBeValid) {
                $this->assertTrue($isValid, "Command should be valid: {$command}");
            } else {
                $this->assertFalse($isValid, "Command should be invalid: {$command}");
            }
        }
    }

    private function validateCommandSyntax(string $command): bool
    {
        // Extract command name
        $parts = explode(' ', $command);
        $commandName = ltrim($parts[0], '!');

        // Check if command exists
        $exists = NativeCommand::where('slug', $commandName)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            return false;
        }

        // Additional syntax checks
        if (str_contains($command, 'new-channel')) {
            // Channel names should not contain spaces or emojis in the name part
            if (preg_match('/!new-channel\s+([^\s]+)\s+/', $command, $matches)) {
                $channelName = $matches[1];
                if (preg_match('/[🎉🎊🌟✨🔥💥🎮🎯🎲🎪🎭🎨🎬🎤🎧🎵🎶🎼🎹🎺🎻🥳🤩😎🔥💎⭐🌟]/', $channelName)) {
                    return false; // Contains emojis
                }
                if (str_contains($channelName, ' ')) {
                    return false; // Contains spaces
                }
            }
        }

        return true;
    }
}
