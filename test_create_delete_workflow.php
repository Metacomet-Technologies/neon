#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use OpenAI\Laravel\Facades\OpenAI;

echo "🔬 Testing Enhanced Create-Then-Delete Workflow\n";
echo str_repeat('=', 50) . "\n\n";

// Test the problematic create-then-delete scenario
$query = 'create 5 test categories and then delete them all';

echo "🎯 Testing Query: '$query'\n\n";

try {
    $request = new NativeCommandRequest([
        'guild_id' => '123456789012345678',
        'channel_id' => '987654321098765432',
        'discord_user_id' => '555666777888999000',
        'message_content' => "!neon {$query}",
        'command' => ['slug' => 'neon'],
    ]);

    $job = new ProcessNeonChatGPTJob($request);
    $reflection = new ReflectionClass($job);

    // Build system prompt
    $systemMethod = $reflection->getMethod('buildSystemPrompt');
    $systemMethod->setAccessible(true);
    $systemPrompt = $systemMethod->invoke($job);

    // Build user prompt
    $userMethod = $reflection->getMethod('buildUserPrompt');
    $userMethod->setAccessible(true);

    $queryProperty = $reflection->getProperty('userQuery');
    $queryProperty->setAccessible(true);
    $queryProperty->setValue($job, $query);

    $userPrompt = $userMethod->invoke($job);

    echo "✅ System prompt includes create+delete handling: " . (strpos($systemPrompt, 'SPECIAL HANDLING FOR CREATE+DELETE') !== false ? 'Yes' : 'No') . "\n";
    echo "✅ System prompt mentions separate commands: " . (strpos($systemPrompt, 'separate delete command') !== false ? 'Yes' : 'No') . "\n\n";

    // Make OpenAI API call
    echo "🔄 Calling ChatGPT API...\n";
    $response = OpenAI::chat()->create([
        'model' => config('openai.model', 'gpt-3.5-turbo'),
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => 800,
        'temperature' => 0.2,
    ]);

    $content = $response->choices[0]->message->content;
    echo "🤖 ChatGPT Response:\n";
    echo $content . "\n\n";

    // Try to parse the JSON response
    $jsonStart = strpos($content, '{');
    $jsonEnd = strrpos($content, '}');

    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);

        if ($parsed && isset($parsed['synopsis']) && isset($parsed['discord_commands'])) {
            echo "📊 Analysis:\n";
            echo "- Synopsis: " . $parsed['synopsis'] . "\n";
            echo "- Command count: " . count($parsed['discord_commands']) . "\n";
            echo "- Contains delete commands: " . (count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'delete') !== false)) > 0 ? 'Yes' : 'No') . "\n";
            echo "- Contains only create commands: " . (count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'new-') !== false)) === count($parsed['discord_commands']) ? 'Yes' : 'No') . "\n";
            echo "- Mentions separate delete step: " . (strpos($parsed['synopsis'], 'separate') !== false || strpos($parsed['synopsis'], 'afterward') !== false ? 'Yes' : 'No') . "\n\n";

            echo "📝 Generated Commands:\n";
            foreach ($parsed['discord_commands'] as $i => $cmd) {
                echo (($i + 1) . ". $cmd\n");
            }

            // Test the workflow fix
            if (count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'delete') !== false)) === 0 &&
                count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'new-') !== false)) > 0 &&
                (strpos($parsed['synopsis'], 'separate') !== false || strpos($parsed['synopsis'], 'afterward') !== false)) {
                echo "\n🎉 SUCCESS: Create-then-delete workflow properly handled!\n";
                echo "✅ Only creation commands provided\n";
                echo "✅ Synopsis mentions separate deletion step\n";
                echo "✅ No impossible ID predictions\n";
            } else {
                echo "\n⚠️ PARTIAL: Workflow needs adjustment\n";
            }
        } else {
            echo "❌ Failed to parse JSON response\n";
        }
    } else {
        echo "❌ No JSON found in response\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
