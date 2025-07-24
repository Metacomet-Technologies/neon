#!/usr/bin/env php
<?php

/**
 * Extended Live Testing Suite for ChatGPT Integration
 * Tests edge cases and complex scenarios
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

echo "🚀 Extended ChatGPT Live Testing Suite\n";
echo str_repeat('=', 50) . "\n\n";

// Advanced test scenarios
$advancedScenarios = [
    [
        'name' => 'Complex Server Setup',
        'query' => 'I want to create a comprehensive setup for a gaming community with different channels for various games, roles for skill levels, and proper organization',
        'expected_min_commands' => 5,
        'should_include_types' => ['new-category', 'new-channel', 'new-role']
    ],
    [
        'name' => 'Moderation System',
        'query' => 'set up a complete moderation system with staff channels, timeout capabilities, and proper permissions',
        'expected_min_commands' => 3,
        'should_include_types' => ['new-role', 'lock-channel', 'new-channel']
    ],
    [
        'name' => 'New Member Onboarding',
        'query' => 'create a welcoming system for new members with introduction channels, verification process, and newcomer roles',
        'expected_min_commands' => 3,
        'should_include_types' => ['new-channel', 'new-role']
    ],
    [
        'name' => 'Event Management',
        'query' => 'help me organize events with scheduled announcements and dedicated channels for event coordination',
        'expected_min_commands' => 2,
        'should_include_types' => ['new-channel', 'scheduled-message']
    ],
    [
        'name' => 'Channel Cleanup',
        'query' => 'clean up inactive channels by locking them and creating a proper archive system',
        'expected_min_commands' => 2,
        'should_include_types' => ['lock-channel', 'new-category']
    ],
    [
        'name' => 'Community Engagement',
        'query' => 'boost community engagement with polls, announcements, and interactive channels',
        'expected_min_commands' => 2,
        'should_include_types' => ['poll', 'notify']
    ]
];

$totalTests = count($advancedScenarios);
$passedTests = 0;
$allResults = [];

foreach ($advancedScenarios as $index => $scenario) {
    $testNumber = $index + 1;
    echo "🧪 Advanced Test {$testNumber}/{$totalTests}: {$scenario['name']}\n";
    echo "Query: \"{$scenario['query']}\"\n";
    echo str_repeat('-', 60) . "\n";

    try {
        $result = runAdvancedTest($scenario['query']);

        if ($result['success']) {
            $validation = validateAdvancedResult($result, $scenario);

            if ($validation['passed']) {
                $passedTests++;
                echo "✅ PASS - Generated " . count($result['commands']) . " commands\n";
                echo "Synopsis: {$result['synopsis']}\n";
                echo "Commands:\n";
                foreach ($result['commands'] as $i => $cmd) {
                    echo "  " . ($i + 1) . ". {$cmd}\n";
                }
            } else {
                echo "❌ FAIL: {$validation['reason']}\n";
                echo "Generated " . count($result['commands']) . " commands:\n";
                foreach ($result['commands'] as $i => $cmd) {
                    echo "  " . ($i + 1) . ". {$cmd}\n";
                }
            }

            $allResults[$scenario['name']] = [
                'passed' => $validation['passed'],
                'commands' => $result['commands'],
                'synopsis' => $result['synopsis'],
                'reason' => $validation['reason']
            ];
        } else {
            echo "❌ ERROR: {$result['error']}\n";
            $allResults[$scenario['name']] = [
                'passed' => false,
                'error' => $result['error']
            ];
        }

    } catch (Exception $e) {
        echo "💥 EXCEPTION: {$e->getMessage()}\n";
        $allResults[$scenario['name']] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
    }

    echo "\n";

    // Rate limiting delay
    if ($testNumber < $totalTests) {
        echo "⏳ Waiting 3 seconds to avoid rate limiting...\n\n";
        sleep(3);
    }
}

// Final comprehensive results
echo str_repeat('=', 60) . "\n";
echo "🏆 COMPREHENSIVE TEST RESULTS\n";
echo str_repeat('=', 60) . "\n";

$passRate = round(($passedTests / $totalTests) * 100, 1);
echo "Overall Success Rate: {$passedTests}/{$totalTests} ({$passRate}%)\n\n";

// Detailed breakdown
foreach ($allResults as $testName => $result) {
    $status = $result['passed'] ? '✅' : '❌';
    echo "{$status} {$testName}\n";

    if ($result['passed']) {
        echo "   Commands: " . count($result['commands']) . "\n";
        echo "   Synopsis: " . substr($result['synopsis'], 0, 80) . "...\n";
    } else {
        echo "   Issue: " . ($result['error'] ?? $result['reason']) . "\n";
    }
    echo "\n";
}

// Performance analysis
echo str_repeat('-', 60) . "\n";
echo "📊 PERFORMANCE ANALYSIS\n";
echo str_repeat('-', 60) . "\n";

if ($passRate >= 90) {
    echo "🌟 EXCELLENT: System is production-ready!\n";
    echo "   - High accuracy in command generation\n";
    echo "   - Proper syntax validation working\n";
    echo "   - Complex scenarios handled well\n";
} elseif ($passRate >= 75) {
    echo "👍 VERY GOOD: System performs well with minor improvements needed\n";
    echo "   - Most scenarios work correctly\n";
    echo "   - Some edge cases may need refinement\n";
} elseif ($passRate >= 60) {
    echo "⚠️ GOOD: System functional but needs optimization\n";
    echo "   - Basic functionality working\n";
    echo "   - Complex scenarios need improvement\n";
} else {
    echo "🔧 NEEDS WORK: System requires significant improvements\n";
    echo "   - Review prompt engineering\n";
    echo "   - Check validation logic\n";
    echo "   - Consider model fine-tuning\n";
}

echo "\n💡 RECOMMENDATIONS:\n";
echo "1. Monitor real-world usage patterns\n";
echo "2. Collect user feedback for continuous improvement\n";
echo "3. Add more specific examples for complex scenarios\n";
echo "4. Consider implementing learning from successful patterns\n";

function runAdvancedTest(string $query): array
{
    try {
        // Create test request
        $request = new NativeCommandRequest([
            'guild_id' => '123456789012345678',
            'channel_id' => '987654321098765432',
            'discord_user_id' => '555666777888999000',
            'message_content' => "!neon {$query}",
            'command' => ['slug' => 'neon'],
        ]);

        $job = new ProcessNeonChatGPTJob($request);
        $reflection = new ReflectionClass($job);

        // Build prompts
        $systemPromptMethod = $reflection->getMethod('buildSystemPrompt');
        $systemPromptMethod->setAccessible(true);
        $systemPrompt = $systemPromptMethod->invoke($job);

        $userPromptMethod = $reflection->getMethod('buildUserPrompt');
        $userPromptMethod->setAccessible(true);

        $queryProperty = $reflection->getProperty('userQuery');
        $queryProperty->setAccessible(true);
        $queryProperty->setValue($job, $query);

        $userPrompt = $userPromptMethod->invoke($job);

        // Make OpenAI API call
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
            'max_tokens' => 1500, // Increased for complex scenarios
            'temperature' => 0.3,
        ]);

        $chatGptResponse = $response->choices[0]->message->content ?? null;

        if (!$chatGptResponse) {
            return ['success' => false, 'error' => 'No response from ChatGPT'];
        }

        // Parse response
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

function validateAdvancedResult(array $result, array $scenario): array
{
    $errors = [];

    // Check minimum number of commands
    if (count($result['commands']) < $scenario['expected_min_commands']) {
        $errors[] = "Expected at least {$scenario['expected_min_commands']} commands, got " . count($result['commands']);
    }

    // Check for required command types
    foreach ($scenario['should_include_types'] as $requiredType) {
        $found = false;
        foreach ($result['commands'] as $command) {
            if (str_contains($command, $requiredType)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "Missing required command type: {$requiredType}";
        }
    }

    // Validate command syntax
    foreach ($result['commands'] as $command) {
        if (!str_starts_with($command, '!')) {
            $errors[] = "Command doesn't start with !: {$command}";
        }

        // Validate against available commands
        $parts = explode(' ', $command);
        $commandName = ltrim($parts[0], '!');

        $isValidCommand = DB::table('native_commands')
            ->where('slug', $commandName)
            ->where('is_active', true)
            ->exists();

        if (!$isValidCommand) {
            $errors[] = "Invalid command: {$commandName}";
        }
    }

    // Check synopsis quality
    if (empty($result['synopsis']) || strlen($result['synopsis']) < 20) {
        $errors[] = "Synopsis too short or missing";
    }

    return [
        'passed' => empty($errors),
        'reason' => empty($errors) ? 'All validations passed' : implode('; ', $errors)
    ];
}
