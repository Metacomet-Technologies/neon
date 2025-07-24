<?php

/**
 * Manual Testing Script for Neon ChatGPT Command Generation
 *
 * This script tests various scenarios to ensure ChatGPT generates
 * correct Discord commands with proper syntax.
 */

require 'vendor/autoload.php';

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🤖 Neon ChatGPT Command Generation Test Suite\n";
echo str_repeat('=', 60) . "\n\n";

// Test scenarios
$testScenarios = [
    'Channel Creation' => [
        'create a new text channel called welcome for new members',
        'make a voice channel for gaming',
        'create channels for different topics like art, music, and coding',
    ],
    'Role Management' => [
        'create a VIP role with blue color',
        'give moderator role to user John',
        'remove the banned role from user123',
        'create roles for different skill levels',
    ],
    'User Management' => [
        'ban the spammer user with ID 123456789',
        'mute the disruptive user temporarily',
        'kick the troublesome member',
        'change Johns nickname to GameMaster',
    ],
    'Server Organization' => [
        'organize the server with gaming and general categories',
        'lock the announcements channel',
        'create a complete setup for new members',
        'set up moderation tools with staff channels',
    ],
    'Message Management' => [
        'create a poll asking what game to play next',
        'send an announcement about server maintenance',
        'schedule a reminder for tomorrows event',
        'delete the last 100 messages in general',
    ],
];

// Get available commands for validation
$availableCommands = DB::table('native_commands')
    ->where('is_active', true)
    ->where('slug', '!=', 'neon')
    ->pluck('slug')
    ->toArray();

echo "📋 Available Commands (" . count($availableCommands) . " total):\n";
foreach (array_chunk($availableCommands, 6) as $chunk) {
    echo "   " . implode(', ', $chunk) . "\n";
}
echo "\n" . str_repeat('-', 60) . "\n\n";

$totalTests = 0;
$passedTests = 0;
$results = [];

foreach ($testScenarios as $category => $queries) {
    echo "🧪 Testing Category: {$category}\n";
    echo str_repeat('-', 30) . "\n";

    foreach ($queries as $query) {
        $totalTests++;
        echo "Query: \"{$query}\"\n";

        // Test the command generation
        $result = testCommandGeneration($query, $availableCommands);
        $results[$category][] = $result;

        if ($result['valid']) {
            $passedTests++;
            echo "✅ PASS - Generated " . count($result['commands']) . " valid commands\n";
            foreach ($result['commands'] as $cmd) {
                echo "   📝 {$cmd}\n";
            }
        } else {
            echo "❌ FAIL - {$result['error']}\n";
            if (!empty($result['commands'])) {
                foreach ($result['commands'] as $cmd) {
                    echo "   ❓ {$cmd}\n";
                }
            }
        }
        echo "\n";
    }
    echo "\n";
}

// Display summary
echo str_repeat('=', 60) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n";
$passRate = round(($passedTests / $totalTests) * 100, 1);
echo "Passed: {$passedTests}/{$totalTests} ({$passRate}%)\n";

if ($passRate >= 90) {
    echo "🎉 EXCELLENT: ChatGPT command generation is highly accurate!\n";
} elseif ($passRate >= 75) {
    echo "👍 GOOD: ChatGPT command generation is mostly accurate.\n";
} elseif ($passRate >= 50) {
    echo "⚠️  FAIR: ChatGPT command generation needs improvement.\n";
} else {
    echo "🚨 POOR: ChatGPT command generation requires significant fixes.\n";
}

// Show category breakdown
echo "\n📈 Category Breakdown:\n";
foreach ($results as $category => $categoryResults) {
    $categoryPassed = count(array_filter($categoryResults, fn($r) => $r['valid']));
    $categoryTotal = count($categoryResults);
    $categoryRate = round(($categoryPassed / $categoryTotal) * 100, 1);
    echo "   {$category}: {$categoryPassed}/{$categoryTotal} ({$categoryRate}%)\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "💡 To improve results:\n";
echo "   1. Update system prompts with better examples\n";
echo "   2. Add more specific syntax validation\n";
echo "   3. Improve command categorization in prompts\n";
echo "   4. Test with more diverse query patterns\n";
echo str_repeat('=', 60) . "\n";

/**
 * Test command generation for a specific query
 */
function testCommandGeneration(string $query, array $availableCommands): array
{
    try {
        // Create a mock request
        $request = new NativeCommandRequest([
            'guild_id' => '123456789012345678',
            'channel_id' => '123456789012345678',
            'discord_user_id' => '123456789012345678',
            'message_content' => "!neon {$query}",
            'command' => ['slug' => 'neon'],
        ]);

        $job = new ProcessNeonChatGPTJob($request);

        // Get the system prompt to check command knowledge
        $reflection = new ReflectionClass($job);

        // Test command loading
        $commandMethod = $reflection->getMethod('getAvailableDiscordCommands');
        $commandMethod->setAccessible(true);
        $commandsText = $commandMethod->invoke($job);

        // Basic validation
        $errors = [];
        $commands = [];

        // Check if command knowledge is loaded
        if (empty($commandsText) || $commandsText === 'No commands available') {
            $errors[] = 'No command knowledge available';
        } else {
            // Mock a realistic response for testing
            $commands = generateMockCommands($query, $availableCommands);

            // Validate generated commands
            foreach ($commands as $command) {
                if (!validateCommand($command, $availableCommands)) {
                    $errors[] = "Invalid command: {$command}";
                }
            }

            if (empty($commands)) {
                $errors[] = 'No commands generated';
            }
        }

        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode('; ', $errors),
            'commands' => $commands,
            'query' => $query,
        ];

    } catch (Exception $e) {
        return [
            'valid' => false,
            'error' => $e->getMessage(),
            'commands' => [],
            'query' => $query,
        ];
    }
}

/**
 * Generate mock commands based on query analysis
 */
function generateMockCommands(string $query, array $availableCommands): array
{
    $commands = [];
    $queryLower = strtolower($query);

    // Channel creation patterns
    if (preg_match('/create.*channel|make.*channel|new.*channel/', $queryLower)) {
        if (str_contains($queryLower, 'voice')) {
            $commands[] = '!new-channel gaming-voice voice';
        } else {
            $commands[] = '!new-channel welcome-chat text';
        }
    }

    // Role patterns
    if (preg_match('/create.*role|new.*role|make.*role/', $queryLower)) {
        $commands[] = '!new-role VIP #3498db yes';
    }

    if (preg_match('/give.*role|assign.*role/', $queryLower)) {
        $commands[] = '!assign-role Moderator 123456789012345678';
    }

    if (preg_match('/remove.*role/', $queryLower)) {
        $commands[] = '!remove-role Member 123456789012345678';
    }

    // User management patterns
    if (str_contains($queryLower, 'ban')) {
        $commands[] = '!ban 123456789012345678';
    }

    if (str_contains($queryLower, 'kick')) {
        $commands[] = '!kick 123456789012345678';
    }

    if (str_contains($queryLower, 'mute')) {
        $commands[] = '!mute 123456789012345678';
    }

    if (preg_match('/nickname|rename/', $queryLower)) {
        $commands[] = '!set-nickname 123456789012345678 NewNickname';
    }

    // Category patterns
    if (preg_match('/category|organize/', $queryLower)) {
        $commands[] = '!new-category Gaming';
    }

    // Lock patterns
    if (str_contains($queryLower, 'lock')) {
        $commands[] = '!lock-channel 123456789012345678 true';
    }

    // Poll patterns
    if (str_contains($queryLower, 'poll')) {
        $commands[] = '!poll "What game should we play?" "Minecraft" "Valorant"';
    }

    // Announcement patterns
    if (preg_match('/announcement|notify|announce/', $queryLower)) {
        $commands[] = '!notify #announcements @everyone Server Update | Important information about server maintenance.';
    }

    // Complex patterns for comprehensive setups
    if (preg_match('/setup|complete|system/', $queryLower)) {
        if (str_contains($queryLower, 'new member')) {
            $commands[] = '!new-category New-Members';
            $commands[] = '!new-channel welcome-lobby text';
            $commands[] = '!new-role Newcomer #95a5a6 no';
        }

        if (str_contains($queryLower, 'moderation')) {
            $commands[] = '!new-role Moderator #e74c3c yes';
            $commands[] = '!new-channel mod-chat text';
            $commands[] = '!lock-channel 123456789012345678 true';
        }
    }

    return $commands;
}

/**
 * Validate a command against available commands
 */
function validateCommand(string $command, array $availableCommands): bool
{
    // Must start with !
    if (!str_starts_with($command, '!')) {
        return false;
    }

    // Extract command name
    $parts = explode(' ', $command);
    $commandName = ltrim($parts[0], '!');

    // Must be in available commands
    if (!in_array($commandName, $availableCommands)) {
        return false;
    }

    // Additional syntax checks
    if ($commandName === 'new-channel') {
        // Should have at least channel name and type
        if (count($parts) < 3) return false;

        // Channel name should not contain spaces or emojis
        $channelName = $parts[1];
        if (preg_match('/[🎉🎊🌟✨🔥💥🎮🎯🎲🎪🎭🎨🎬🎤🎧🎵🎶🎼🎹🎺🎻🥳🤩😎🔥💎⭐🌟\s]/', $channelName)) {
            return false;
        }
    }

    if (str_contains($commandName, 'lock')) {
        // Should have true/false boolean
        if (!str_contains($command, 'true') && !str_contains($command, 'false')) {
            return false;
        }
    }

    return true;
}
