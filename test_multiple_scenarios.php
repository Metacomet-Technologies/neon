#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use OpenAI\Laravel\Facades\OpenAI;

echo "🔬 Testing Multiple Create-Delete Scenarios\n";
echo str_repeat('=', 50) . "\n\n";

// Test different create-then-delete scenarios
$testCases = [
    'create a welcome category with channels and delete it afterward',
    'set up test channels and remove them when done',
    'create gaming area and then clean it up',
    'make some temporary roles and delete them later'
];

foreach ($testCases as $index => $query) {
    echo "🎯 Test " . ($index + 1) . ": '$query'\n";
    echo str_repeat('-', 40) . "\n";

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

        $systemMethod = $reflection->getMethod('buildSystemPrompt');
        $systemMethod->setAccessible(true);
        $systemPrompt = $systemMethod->invoke($job);

        $userMethod = $reflection->getMethod('buildUserPrompt');
        $userMethod->setAccessible(true);

        $queryProperty = $reflection->getProperty('userQuery');
        $queryProperty->setAccessible(true);
        $queryProperty->setValue($job, $query);

        $userPrompt = $userMethod->invoke($job);

        // Make OpenAI API call
        $response = OpenAI::chat()->create([
            'model' => config('openai.model', 'gpt-3.5-turbo'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'max_tokens' => 600,
            'temperature' => 0.2,
        ]);

        $content = $response->choices[0]->message->content;

        // Parse JSON response
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);

            if ($parsed && isset($parsed['synopsis']) && isset($parsed['discord_commands'])) {
                $hasDeleteCommands = count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'delete') !== false)) > 0;
                $hasOnlyCreateCommands = count(array_filter($parsed['discord_commands'], fn($cmd) => strpos($cmd, 'new-') !== false)) === count($parsed['discord_commands']);
                $mentionsSeparate = strpos($parsed['synopsis'], 'separate') !== false || strpos($parsed['synopsis'], 'afterward') !== false;

                echo "📊 Results:\n";
                echo "- Commands: " . count($parsed['discord_commands']) . "\n";
                echo "- Delete commands: " . ($hasDeleteCommands ? 'Yes' : 'No') . "\n";
                echo "- Only create commands: " . ($hasOnlyCreateCommands ? 'Yes' : 'No') . "\n";
                echo "- Mentions separation: " . ($mentionsSeparate ? 'Yes' : 'No') . "\n";

                if (!$hasDeleteCommands && $hasOnlyCreateCommands && $mentionsSeparate) {
                    echo "✅ PASS: Workflow handled correctly\n";
                } else {
                    echo "⚠️ PARTIAL: Needs review\n";
                    echo "Synopsis: " . $parsed['synopsis'] . "\n";
                }
            } else {
                echo "❌ FAIL: Could not parse response\n";
            }
        } else {
            echo "❌ FAIL: No JSON in response\n";
        }

    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}
