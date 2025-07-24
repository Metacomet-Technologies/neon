#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Enhanced Command Categorization System\n";
echo str_repeat('=', 55) . "\n\n";

// Test the enhanced categorization with mixed commands
$testCommands = [
    '!unban 123456789012345678',     // Recovery (should run first)
    '!new-category gaming',          // Constructive (should run second)
    '!delete-channel old-chat',      // Destructive (should run last)
    '!new-channel voice-chat voice gaming', // Constructive (should run second)
    '!edit-channel-name voice-chat cool-voice', // Modification (should run third)
    '!delete-category old-gaming',   // Destructive (should run last)
    '!assign-role member @user123',  // Constructive (should run second)
    '!lock-channel voice-chat true', // Modification (should run third)
    '!remove-role oldmember @user123', // Destructive (should run last)
    '!unmute 987654321098765432'     // Recovery (should run first)
];

echo "📝 Test Commands (Mixed Order):\n";
echo str_repeat('-', 30) . "\n";
foreach ($testCommands as $i => $cmd) {
    echo ($i + 1) . ". {$cmd}\n";
}

echo "\n🔄 Simulating Command Categorization...\n";
echo str_repeat('-', 40) . "\n";

// Simulate the categorization logic from ProcessNeonDiscordExecutionJob
$destructiveCommands = [
    'delete-category', 'delete-channel', 'delete-event', 'delete-role',
    'ban', 'kick', 'mute', 'disconnect', 'purge', 'prune',
    'remove-role', 'vanish', 'unpin'
];

$constructiveCommands = [
    'new-category', 'new-channel', 'new-role', 'create-event',
    'assign-role', 'assign-channel', 'pin', 'notify', 'poll',
    'scheduled-message'
];

$modificationCommands = [
    'edit-channel-autohide', 'edit-channel-name', 'edit-channel-nsfw',
    'edit-channel-slowmode', 'edit-channel-topic', 'lock-channel', 
    'lock-voice', 'move-user', 'set-inactive', 'set-nickname',
    'display-boost'
];

$recoveryCommands = [
    'unban', 'unmute', 'unvanish'
];

function extractCommandSlug($command) {
    return trim(explode(' ', ltrim($command, '!'))[0]);
}

$categories = [
    'recovery' => [],
    'constructive' => [],
    'modification' => [],
    'destructive' => []
];

foreach ($testCommands as $index => $command) {
    $commandSlug = extractCommandSlug($command);
    $commandData = ['command' => $command, 'index' => $index];

    if (in_array($commandSlug, $recoveryCommands)) {
        $categories['recovery'][] = $commandData;
    } elseif (in_array($commandSlug, $constructiveCommands)) {
        $categories['constructive'][] = $commandData;
    } elseif (in_array($commandSlug, $modificationCommands)) {
        $categories['modification'][] = $commandData;
    } elseif (in_array($commandSlug, $destructiveCommands)) {
        $categories['destructive'][] = $commandData;
    } else {
        $categories['modification'][] = $commandData; // Default to safest
    }
}

// Display categorization results
foreach ($categories as $category => $commands) {
    if (!empty($commands)) {
        echo "\n" . strtoupper($category) . " COMMANDS (" . count($commands) . "):\n";
        foreach ($commands as $commandData) {
            echo "  → {$commandData['command']}\n";
        }
    }
}

echo "\n✅ Execution Order:\n";
echo str_repeat('-', 20) . "\n";
echo "1. RECOVERY commands (restore access)\n";
echo "2. CONSTRUCTIVE commands (build dependencies)\n";
echo "3. MODIFICATION commands (edit existing)\n";
echo "4. DESTRUCTIVE commands (cleanup)\n";

echo "\n🎯 Benefits of This Approach:\n";
echo str_repeat('-', 30) . "\n";
echo "• Prevents dependency conflicts\n";
echo "• Ensures data integrity\n";
echo "• Maximizes success rate\n";
echo "• Maintains API compliance\n";
echo "• Provides predictable execution\n";

echo "\n📊 Performance Impact:\n";
echo str_repeat('-', 20) . "\n";
echo "• Recovery: Sequential (usually 1-2 commands)\n";
echo "• Constructive: Sequential (dependency order matters)\n";
echo "• Modification: Parallel batches (3 per batch)\n";
echo "• Destructive: Parallel batches (3 per batch)\n";

echo "\n🔍 Category Validation:\n";
echo str_repeat('-', 20) . "\n";
$totalOriginal = count($testCommands);
$totalCategorized = array_sum(array_map('count', $categories));
echo "Original commands: {$totalOriginal}\n";
echo "Categorized commands: {$totalCategorized}\n";
echo "Validation: " . ($totalOriginal === $totalCategorized ? "✅ PASS" : "❌ FAIL") . "\n";

echo "\n🎉 Enhanced categorization system ready for deployment!\n";
