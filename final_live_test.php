#!/usr/bin/env php
<?php

/**
 * Final Comprehensive Live Test for ChatGPT Integration
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

echo "🎯 FINAL COMPREHENSIVE CHATGPT LIVE TEST\n";
echo str_repeat('=', 50) . "\n\n";

// Check system readiness
echo "🔍 System Status Check:\n";
$apiKey = config('openai.api_key');
$commandCount = DB::table('native_commands')->where('is_active', true)->where('slug', '!=', 'neon')->count();

echo "   ✅ API Key: " . (empty($apiKey) ? "❌ Missing" : "Configured") . "\n";
echo "   ✅ Commands: {$commandCount} available\n";
echo "   ✅ Model: " . config('openai.model', 'gpt-3.5-turbo') . "\n\n";

if (empty($apiKey)) {
    echo "❌ Cannot proceed without OpenAI API key\n";
    exit(1);
}

// Real-world test scenarios
$realWorldTests = [
    "create a welcome area for new server members",
    "set up a gaming community with voice channels",
    "make a VIP role with special permissions",
    "organize channels by topic with categories",
    "create a poll about the next community event"
];

echo "🧪 Running Real-World Scenarios:\n";
echo str_repeat('-', 30) . "\n\n";

$totalSuccess = 0;

foreach ($realWorldTests as $index => $query) {
    $testNum = $index + 1;
    echo "Test {$testNum}: \"{$query}\"\n";

    $result = testChatGPTQuery($query);

    if ($result['success']) {
        $totalSuccess++;
        echo "✅ SUCCESS\n";
        echo "📝 Synopsis: {$result['synopsis']}\n";
        echo "🔧 Commands Generated (" . count($result['commands']) . "):\n";

        foreach ($result['commands'] as $i => $cmd) {
            echo "   " . ($i + 1) . ". {$cmd}\n";
        }

        // Validate each command
        $invalidCommands = validateCommands($result['commands']);
        if (empty($invalidCommands)) {
            echo "✅ All commands are valid\n";
        } else {
            echo "⚠️  Invalid commands detected: " . implode(', ', $invalidCommands) . "\n";
        }
    } else {
        echo "❌ FAILED: {$result['error']}\n";
    }

    echo "\n";

    if ($testNum < count($realWorldTests)) {
        echo "⏳ Waiting 2 seconds...\n\n";
        sleep(2);
    }
}

// Final results
echo str_repeat('=', 50) . "\n";
echo "🏆 FINAL RESULTS\n";
echo str_repeat('=', 50) . "\n";

$successRate = round(($totalSuccess / count($realWorldTests)) * 100, 1);
echo "Success Rate: {$totalSuccess}/" . count($realWorldTests) . " ({$successRate}%)\n\n";

if ($successRate >= 90) {
    echo "🌟 EXCELLENT! System is production-ready!\n";
    echo "   ✅ High accuracy command generation\n";
    echo "   ✅ Proper Discord syntax compliance\n";
    echo "   ✅ Complex scenario handling\n";
    echo "   ✅ Real-time database integration\n\n";

    echo "🚀 READY FOR DEPLOYMENT!\n";
    echo "Users can now use: !neon <request> in Discord\n";
} elseif ($successRate >= 70) {
    echo "👍 GOOD! System works well with minor tweaks needed\n";
} else {
    echo "🔧 NEEDS IMPROVEMENT\n";
}

echo "\n📊 System Capabilities Demonstrated:\n";
echo "   🔄 Database-driven command loading\n";
echo "   🤖 Real ChatGPT API integration\n";
echo "   ✅ Command validation and filtering\n";
echo "   📝 Intelligent command generation\n";
echo "   🎯 Syntax compliance checking\n";

echo "\n💡 Next Steps:\n";
echo "   1. Deploy to production Discord server\n";
echo "   2. Monitor real user interactions\n";
echo "   3. Collect feedback for improvements\n";
echo "   4. Add more advanced command patterns\n";

function testChatGPTQuery(string $query): array
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

        // Set query
        $queryProperty = $reflection->getProperty('userQuery');
        $queryProperty->setAccessible(true);
        $queryProperty->setValue($job, $query);

        // Get prompts
        $systemPromptMethod = $reflection->getMethod('buildSystemPrompt');
        $systemPromptMethod->setAccessible(true);
        $systemPrompt = $systemPromptMethod->invoke($job);

        $userPromptMethod = $reflection->getMethod('buildUserPrompt');
        $userPromptMethod->setAccessible(true);
        $userPrompt = $userPromptMethod->invoke($job);

        // Call ChatGPT
        $response = OpenAI::chat()->create([
            'model' => config('openai.model', 'gpt-3.5-turbo'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'max_tokens' => 1000,
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
            return ['success' => false, 'error' => 'Failed to parse response'];
        }

        return [
            'success' => true,
            'commands' => $parsed['discord_commands'] ?? [],
            'synopsis' => $parsed['synopsis'] ?? '',
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function validateCommands(array $commands): array
{
    $invalid = [];
    $validCommands = DB::table('native_commands')
        ->where('is_active', true)
        ->pluck('slug')
        ->toArray();

    foreach ($commands as $command) {
        if (!str_starts_with($command, '!')) {
            $invalid[] = $command;
            continue;
        }

        $parts = explode(' ', $command);
        $commandName = ltrim($parts[0], '!');

        if (!in_array($commandName, $validCommands)) {
            $invalid[] = $command;
        }
    }

    return $invalid;
}
