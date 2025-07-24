#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use Illuminate\Support\Facades\Cache;

echo "🔍 Debug: Checking ChatGPT Command System\n";
echo str_repeat('=', 40) . "\n\n";

// 1. Check if commands are loaded
echo "1️⃣ Checking command loading...\n";
$request = new NativeCommandRequest([
    'guild_id' => '123456789012345678',
    'channel_id' => '987654321098765432',
    'discord_user_id' => '555666777888999000',
    'message_content' => '!neon test',
    'command' => ['slug' => 'neon'],
]);

$job = new ProcessNeonChatGPTJob($request);
$reflection = new ReflectionClass($job);

// Test command loading
$commandMethod = $reflection->getMethod('getAvailableDiscordCommands');
$commandMethod->setAccessible(true);
$commandsText = $commandMethod->invoke($job);

if (strlen($commandsText) > 100) {
    echo "✅ Commands loaded successfully (" . strlen($commandsText) . " characters)\n";
    echo "📋 Sample commands:\n";
    $lines = explode("\n", $commandsText);
    foreach (array_slice($lines, 0, 10) as $line) {
        if (!empty(trim($line))) {
            echo "   {$line}\n";
        }
    }
} else {
    echo "❌ Commands not loaded properly\n";
    echo "Response: {$commandsText}\n";
}

echo "\n";

// 2. Check system prompt
echo "2️⃣ Checking system prompt...\n";
$systemPromptMethod = $reflection->getMethod('buildSystemPrompt');
$systemPromptMethod->setAccessible(true);
$systemPrompt = $systemPromptMethod->invoke($job);

if (str_contains($systemPrompt, 'CRITICAL SYNTAX RULES')) {
    echo "✅ Enhanced system prompt is active\n";
} else {
    echo "❌ Old system prompt detected\n";
}

if (str_contains($systemPrompt, 'new-channel')) {
    echo "✅ Commands are included in prompt\n";
} else {
    echo "❌ Commands not found in prompt\n";
}

echo "📏 System prompt length: " . strlen($systemPrompt) . " characters\n\n";

// 3. Check validation
echo "3️⃣ Checking command validation...\n";
$validateMethod = $reflection->getMethod('validateDiscordCommands');
$validateMethod->setAccessible(true);

// Test valid commands
$validCommands = ['!new-channel test-channel text', '!new-role TestRole'];
$validResult = $validateMethod->invoke($job, $validCommands);
echo "✅ Valid commands test: " . count($validResult) . "/" . count($validCommands) . " passed\n";

// Test invalid commands
$invalidCommands = ['!fake-command test', '!another-invalid'];
$invalidResult = $validateMethod->invoke($job, $invalidCommands);
echo "✅ Invalid commands test: " . count($invalidResult) . " commands passed (should be 0)\n";

echo "\n";

// 4. Check API configuration
echo "4️⃣ Checking OpenAI configuration...\n";
$apiKey = config('openai.api_key');
if (!empty($apiKey)) {
    echo "✅ OpenAI API key configured (length: " . strlen($apiKey) . ")\n";
} else {
    echo "❌ No OpenAI API key found\n";
}

$model = config('openai.model', 'gpt-3.5-turbo');
echo "🤖 Model: {$model}\n";

echo "\n";

// 5. Test command categories
echo "5️⃣ Checking command categories...\n";
$categories = [
    'new-channel' => 'Channel Management',
    'new-role' => 'Role Management',
    'ban' => 'User Management',
    'poll' => 'Message Management',
    'new-category' => 'Category Management'
];

foreach ($categories as $command => $category) {
    if (str_contains($commandsText, $command)) {
        echo "✅ {$category}: {$command} found\n";
    } else {
        echo "❌ {$category}: {$command} missing\n";
    }
}

echo "\n";

// 6. Cache status
echo "6️⃣ Checking cache status...\n";
$cacheKey = 'neon_available_commands';
if (Cache::has($cacheKey)) {
    echo "✅ Commands are cached\n";
    $cached = Cache::get($cacheKey);
    echo "📦 Cached content length: " . strlen($cached) . " characters\n";
} else {
    echo "❌ Commands not cached\n";
}

echo "\n" . str_repeat('=', 40) . "\n";
echo "🎯 System Status Summary:\n";
echo "   Commands: " . (strlen($commandsText) > 100 ? "✅" : "❌") . "\n";
echo "   Prompt: " . (str_contains($systemPrompt, 'CRITICAL SYNTAX RULES') ? "✅" : "❌") . "\n";
echo "   Validation: " . (count($validResult) > 0 && count($invalidResult) == 0 ? "✅" : "❌") . "\n";
echo "   API Key: " . (!empty($apiKey) ? "✅" : "❌") . "\n";
echo "   Cache: " . (Cache::has($cacheKey) ? "✅" : "❌") . "\n";

echo "\n💡 Ready to test ChatGPT integration!\n";
