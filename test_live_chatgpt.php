#!/usr/bin/env php
<?php

/**
 * Simple ChatGPT Command Testing Script
 * Tests the actual ChatGPT integration with real API calls
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

echo "🤖 Neon ChatGPT Live Command Generation Test\n";
echo str_repeat('=', 50) . "\n\n";

// Check if we have an API key
$apiKey = config('openai.api_key');
if (empty($apiKey)) {
    echo "❌ Error: No OpenAI API key configured\n";
    echo "Please set OPENAI_API_KEY in your .env file\n";
    exit(1);
}

echo "✅ OpenAI API key configured\n";
echo "🔄 Loading available commands...\n";

// Get available commands
$commands = DB::table('native_commands')
    ->where('is_active', true)
    ->where('slug', '!=', 'neon')
    ->orderBy('slug')
    ->get(['slug', 'usage', 'example']);

echo "📋 Found " . count($commands) . " available commands\n\n";

// Test scenarios with expected outcomes
$testScenarios = [
    [
        'query' => 'create a welcome channel for new members',
        'expected_commands' => ['new-channel'],
        'should_contain' => ['text', 'welcome'],
        'should_not_contain' => ['🎉', 'Welcome Channel'] // Remove space check as spaces are allowed in descriptions
    ],
    [
        'query' => 'make a VIP role with blue color',
        'expected_commands' => ['new-role'],
        'should_contain' => ['VIP', '#'],
        'should_not_contain' => []
    ],
    [
        'query' => 'ban the spammer user',
        'expected_commands' => ['ban'],
        'should_contain' => ['ban'],
        'should_not_contain' => []
    ],
    [
        'query' => 'create a poll asking what game to play',
        'expected_commands' => ['poll'],
        'should_contain' => ['"', 'poll'],
        'should_not_contain' => []
    ],
    [
        'query' => 'set up channels for gaming with voice and text options',
        'expected_commands' => ['new-channel', 'new-category'],
        'should_contain' => ['text', 'voice'],
        'should_not_contain' => [] // Remove space restriction as descriptions can have spaces
    ]
];

$passedTests = 0;
$totalTests = count($testScenarios);

foreach ($testScenarios as $index => $scenario) {
    $testNumber = $index + 1;
    echo "🧪 Test {$testNumber}/{$totalTests}: {$scenario['query']}\n";
    echo str_repeat('-', 40) . "\n";

    try {
        // Test the actual ChatGPT integration
        $result = testChatGPTGeneration($scenario['query']);

        if ($result['success']) {
            $validation = validateResult($result, $scenario);

            if ($validation['passed']) {
                $passedTests++;
                echo "✅ PASS\n";
                echo "Generated commands:\n";
                foreach ($result['commands'] as $cmd) {
                    echo "  📝 {$cmd}\n";
                }
            } else {
                echo "❌ FAIL: {$validation['reason']}\n";
                echo "Generated commands:\n";
                foreach ($result['commands'] as $cmd) {
                    echo "  ❓ {$cmd}\n";
                }
            }
        } else {
            echo "❌ ERROR: {$result['error']}\n";
        }

    } catch (Exception $e) {
        echo "💥 EXCEPTION: {$e->getMessage()}\n";
    }

    echo "\n";

    // Add delay to avoid rate limiting
    if ($testNumber < $totalTests) {
        echo "⏳ Waiting 2 seconds to avoid rate limiting...\n\n";
        sleep(2);
    }
}

// Display final results
echo str_repeat('=', 50) . "\n";
echo "📊 FINAL RESULTS\n";
echo str_repeat('=', 50) . "\n";
$passRate = round(($passedTests / $totalTests) * 100, 1);
echo "Passed: {$passedTests}/{$totalTests} ({$passRate}%)\n";

if ($passRate >= 80) {
    echo "🎉 EXCELLENT: ChatGPT is generating accurate commands!\n";
} elseif ($passRate >= 60) {
    echo "👍 GOOD: Most commands are accurate, minor improvements needed.\n";
} else {
    echo "⚠️ NEEDS WORK: Command generation needs improvement.\n";
}

echo "\n💡 Tips for improvement:\n";
echo "  - Check system prompt clarity\n";
echo "  - Verify command examples in database\n";
echo "  - Test with more specific queries\n";
echo "  - Monitor ChatGPT temperature setting\n";

/**
 * Test actual ChatGPT command generation
 */
function testChatGPTGeneration(string $query): array
{
    try {
        echo "🔄 Calling ChatGPT API...\n";

        // Create a realistic test request
        $request = new NativeCommandRequest([
            'guild_id' => '123456789012345678',
            'channel_id' => '987654321098765432',
            'discord_user_id' => '555666777888999000',
            'message_content' => "!neon {$query}",
            'command' => ['slug' => 'neon'],
        ]);

        $job = new ProcessNeonChatGPTJob($request);

        // Use reflection to access private methods
        $reflection = new ReflectionClass($job);

        // Get system prompt
        $systemPromptMethod = $reflection->getMethod('buildSystemPrompt');
        $systemPromptMethod->setAccessible(true);
        $systemPrompt = $systemPromptMethod->invoke($job);

        // Get user prompt
        $userPromptMethod = $reflection->getMethod('buildUserPrompt');
        $userPromptMethod->setAccessible(true);

        // Set the user query
        $queryProperty = $reflection->getProperty('userQuery');
        $queryProperty->setAccessible(true);
        $queryProperty->setValue($job, $query);

        $userPrompt = $userPromptMethod->invoke($job);

        echo "📤 Sending request to OpenAI...\n";

        // Make actual OpenAI API call
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
            'temperature' => 0.3, // Lower temperature for more consistent results
        ]);

        $chatGptResponse = $response->choices[0]->message->content ?? null;

        if (!$chatGptResponse) {
            return ['success' => false, 'error' => 'No response from ChatGPT'];
        }

        echo "📥 Received response, parsing...\n";

        // Parse the response
        $parseMethod = $reflection->getMethod('parseChatGPTResponse');
        $parseMethod->setAccessible(true);
        $parsed = $parseMethod->invoke($job, $chatGptResponse);

        if (!$parsed) {
            return [
                'success' => false,
                'error' => 'Failed to parse ChatGPT response',
                'raw_response' => $chatGptResponse
            ];
        }

        return [
            'success' => true,
            'commands' => $parsed['discord_commands'] ?? [],
            'synopsis' => $parsed['synopsis'] ?? '',
            'raw_response' => $chatGptResponse
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Validate the ChatGPT result against expectations
 */
function validateResult(array $result, array $scenario): array
{
    $errors = [];

    // Check if expected commands are present
    foreach ($scenario['expected_commands'] as $expectedCmd) {
        $found = false;
        foreach ($result['commands'] as $command) {
            if (str_contains($command, $expectedCmd)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "Missing expected command: {$expectedCmd}";
        }
    }

    // Check required content
    foreach ($scenario['should_contain'] as $requiredContent) {
        $found = false;
        foreach ($result['commands'] as $command) {
            if (str_contains($command, $requiredContent)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "Missing required content: {$requiredContent}";
        }
    }

    // Check forbidden content
    foreach ($scenario['should_not_contain'] as $forbiddenContent) {
        foreach ($result['commands'] as $command) {
            if (str_contains($command, $forbiddenContent)) {
                $errors[] = "Contains forbidden content '{$forbiddenContent}' in: {$command}";
            }
        }
    }

    // Basic syntax validation
    foreach ($result['commands'] as $command) {
        if (!str_starts_with($command, '!')) {
            $errors[] = "Command doesn't start with !: {$command}";
        }

        // Check for channel name compliance in new-channel commands
        if (str_contains($command, 'new-channel')) {
            if (preg_match('/!new-channel\s+([^\s]+)/', $command, $matches)) {
                $channelName = $matches[1];
                // Only check the channel name part, not the entire command
                if (preg_match('/[A-Z🎉🎊🌟✨🔥💥🎮]/', $channelName)) {
                    $errors[] = "Invalid channel name format: {$channelName} (should be lowercase, no emojis)";
                }
                // Check for spaces in channel name (spaces are only allowed in descriptions)
                if (str_contains($channelName, ' ')) {
                    $errors[] = "Channel name cannot contain spaces: {$channelName}";
                }
            }
        }
    }

    return [
        'passed' => empty($errors),
        'reason' => empty($errors) ? 'All validations passed' : implode('; ', $errors)
    ];
}
