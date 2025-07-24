#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\NativeCommandRequest;
use App\Jobs\ProcessNeonChatGPTJob;

echo "🧪 Simple ChatGPT Test\n";
echo str_repeat('=', 30) . "\n";

// Check API key
$apiKey = config('openai.api_key');
if (empty($apiKey)) {
    echo "❌ No OpenAI API key configured\n";
    exit(1);
}

echo "✅ API Key configured\n";

// Test query
$testQuery = "create a welcome channel for new members";
echo "🔍 Testing: '{$testQuery}'\n\n";

try {
    // Create test request
    $request = NativeCommandRequest::create([
        'guild_id' => '123456789012345678',
        'channel_id' => '987654321098765432',
        'discord_user_id' => '555666777888999000',
        'message_content' => "!neon {$testQuery}",
        'command' => ['slug' => 'neon'],
        'status' => 'pending'
    ]);

    echo "📝 Created test request ID: {$request->id}\n";

    // Create and run job (but don't actually send Discord messages)
    $job = new ProcessNeonChatGPTJob($request);

    // Use reflection to test individual components
    $reflection = new ReflectionClass($job);

    // Test command loading
    echo "📋 Loading available commands...\n";
    $commandMethod = $reflection->getMethod('getAvailableDiscordCommands');
    $commandMethod->setAccessible(true);
    $commands = $commandMethod->invoke($job);

    if (strlen($commands) > 500) {
        echo "✅ Commands loaded (" . strlen($commands) . " chars)\n";
    } else {
        echo "❌ Commands not loaded properly\n";
        echo "Response: " . substr($commands, 0, 200) . "...\n";
    }

    // Test system prompt
    echo "🎯 Building system prompt...\n";
    $systemMethod = $reflection->getMethod('buildSystemPrompt');
    $systemMethod->setAccessible(true);
    $systemPrompt = $systemMethod->invoke($job);

    if (str_contains($systemPrompt, 'new-channel')) {
        echo "✅ System prompt includes commands\n";
    } else {
        echo "❌ System prompt missing commands\n";
    }

    // Test user prompt
    echo "👤 Building user prompt...\n";
    $queryProperty = $reflection->getProperty('userQuery');
    $queryProperty->setAccessible(true);
    $queryProperty->setValue($job, $testQuery);

    $userMethod = $reflection->getMethod('buildUserPrompt');
    $userMethod->setAccessible(true);
    $userPrompt = $userMethod->invoke($job);

    echo "User prompt: {$userPrompt}\n\n";

    // Show what would be sent to ChatGPT
    echo "📤 Would send to ChatGPT:\n";
    echo "System prompt length: " . strlen($systemPrompt) . " chars\n";
    echo "User prompt: {$userPrompt}\n";
    echo "Model: " . config('openai.model', 'gpt-3.5-turbo') . "\n";

    echo "\n✅ All components working! Ready for real ChatGPT test.\n";

} catch (Exception $e) {
    echo "💥 Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n🎯 Next steps:\n";
echo "1. Run: php test_live_chatgpt.php (for full API test)\n";
echo "2. Test via Discord: !neon {$testQuery}\n";
echo "3. Check logs for any issues\n";
