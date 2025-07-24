#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\NativeCommandRequest;
use App\Jobs\ProcessNeonChatGPTJob;
use Illuminate\Support\Facades\Cache;

echo "🧪 Testing Improved ChatGPT Prompts\n";
echo str_repeat('=', 40) . "\n\n";

// Clear any existing cache
Cache::forget('neon_available_commands');

// Create a test request
$testRequest = new NativeCommandRequest([
    'guild_id' => '123456789012345678',
    'channel_id' => '987654321098765432',
    'discord_user_id' => '111222333444555666',
    'message_content' => '!neon create a welcome area with text and voice channels',
    'command' => ['slug' => 'neon'],
    'status' => 'pending',
]);

// Test the improved prompt system
$job = new ProcessNeonChatGPTJob($testRequest);
$reflection = new ReflectionClass($job);

// Test system prompt
$systemMethod = $reflection->getMethod('buildSystemPrompt');
$systemMethod->setAccessible(true);
$systemPrompt = $systemMethod->invoke($job);

echo "✅ System Prompt Generated\n";
echo "Length: " . strlen($systemPrompt) . " characters\n";
echo "Contains workflow rules: " . (strpos($systemPrompt, 'WORKFLOW RULES') !== false ? 'Yes' : 'No') . "\n";
echo "Contains emoji restrictions: " . (strpos($systemPrompt, 'NO SPACES or emojis') !== false ? 'Yes' : 'No') . "\n\n";

// Test user prompt
$userMethod = $reflection->getMethod('buildUserPrompt');
$userMethod->setAccessible(true);

// Set the query for testing
$queryProperty = $reflection->getProperty('userQuery');
$queryProperty->setAccessible(true);
$queryProperty->setValue($job, 'create a welcome area with text and voice channels');

$userPrompt = $userMethod->invoke($job);

echo "✅ User Prompt Generated\n";
echo "Contains simplicity guidance: " . (strpos($userPrompt, 'Keep commands simple') !== false ? 'Yes' : 'No') . "\n";
echo "Contains practical focus: " . (strpos($userPrompt, 'practical, working Discord commands') !== false ? 'Yes' : 'No') . "\n\n";

// Test command loading
$commandMethod = $reflection->getMethod('getAvailableDiscordCommands');
$commandMethod->setAccessible(true);
$commands = $commandMethod->invoke($job);

echo "✅ Commands Loaded from Database\n";
echo "Commands cache size: " . strlen($commands) . " characters\n";
echo "Contains new-channel command: " . (strpos($commands, '!new-channel') !== false ? 'Yes' : 'No') . "\n";
echo "Contains new-category command: " . (strpos($commands, '!new-category') !== false ? 'Yes' : 'No') . "\n\n";

echo "🎯 Key Improvements Made:\n";
echo "1. ✅ Simplified workflow rules - no complex dependent commands\n";
echo "2. ✅ Clear emoji restrictions during channel creation\n";
echo "3. ✅ Lower temperature (0.2) for more consistent results\n";
echo "4. ✅ Focus on functional, working Discord structures\n";
echo "5. ✅ Better guidance for sequential operations\n\n";

echo "🚀 System Ready for Improved Testing!\n";
echo "Try the same command again in Discord to see better results.\n";
