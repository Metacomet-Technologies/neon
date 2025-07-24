#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Discord Command Categorization Analysis\n";
echo str_repeat('=', 50) . "\n\n";

$commands = DB::table('native_commands')
    ->where('is_active', true)
    ->whereNotIn('slug', ['neon', 'help', 'color'])
    ->orderBy('slug')
    ->get(['slug', 'usage', 'description']);

// Categorize commands based on their impact
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

echo "🗑️  DESTRUCTIVE COMMANDS (Delete/Remove/Ban/Destroy):\n";
echo str_repeat('-', 40) . "\n";
foreach ($destructiveCommands as $cmd) {
    $command = $commands->firstWhere('slug', $cmd);
    if ($command) {
        echo "• {$command->slug}: {$command->usage}\n";
    }
}

echo "\n🏗️  CONSTRUCTIVE COMMANDS (Create/Add/Assign):\n";
echo str_repeat('-', 40) . "\n";
foreach ($constructiveCommands as $cmd) {
    $command = $commands->firstWhere('slug', $cmd);
    if ($command) {
        echo "• {$command->slug}: {$command->usage}\n";
    }
}

echo "\n⚙️  MODIFICATION COMMANDS (Edit/Change existing):\n";
echo str_repeat('-', 40) . "\n";
foreach ($modificationCommands as $cmd) {
    $command = $commands->firstWhere('slug', $cmd);
    if ($command) {
        echo "• {$command->slug}: {$command->usage}\n";
    }
}

echo "\n🔄 RECOVERY COMMANDS (Undo destructive actions):\n";
echo str_repeat('-', 40) . "\n";
foreach ($recoveryCommands as $cmd) {
    $command = $commands->firstWhere('slug', $cmd);
    if ($command) {
        echo "• {$command->slug}: {$command->usage}\n";
    }
}

// Check for uncategorized commands
$allCategorized = array_merge($destructiveCommands, $constructiveCommands, $modificationCommands, $recoveryCommands);
$uncategorized = [];

foreach ($commands as $command) {
    if (!in_array($command->slug, $allCategorized)) {
        $uncategorized[] = $command->slug;
    }
}

if (!empty($uncategorized)) {
    echo "\n❓ UNCATEGORIZED COMMANDS:\n";
    echo str_repeat('-', 40) . "\n";
    foreach ($uncategorized as $cmd) {
        $command = $commands->firstWhere('slug', $cmd);
        echo "• {$command->slug}: {$command->usage}\n";
    }
}

echo "\n🚨 CRITICAL CONFLICT SCENARIOS:\n";
echo str_repeat('=', 40) . "\n";
echo "1. Creating channels while deleting categories (dependency conflict)\n";
echo "2. Assigning roles while deleting roles (data corruption)\n";
echo "3. Creating events while purging channels (orphaned data)\n";
echo "4. Setting permissions while deleting channels (invalid references)\n";
echo "5. Moving users while deleting voice channels (broken connections)\n";

echo "\n💡 EXECUTION ORDER STRATEGY:\n";
echo str_repeat('=', 40) . "\n";
echo "1. RECOVERY commands first (unban, unmute, unvanish)\n";
echo "2. CONSTRUCTIVE commands second (create, assign, add)\n";
echo "3. MODIFICATION commands third (edit, lock, move)\n";
echo "4. DESTRUCTIVE commands last (delete, remove, ban)\n";

echo "\n🎯 DEPENDENCY ANALYSIS:\n";
echo str_repeat('=', 40) . "\n";
echo "• Categories must exist before channels can be created in them\n";
echo "• Roles must exist before they can be assigned\n";
echo "• Channels must exist before they can be edited\n";
echo "• Users must be present before roles can be assigned to them\n";
echo "• Events need channels/categories to reference\n";

echo "\n✅ RECOMMENDED IMPLEMENTATION:\n";
echo str_repeat('=', 40) . "\n";
echo "1. Categorize all commands by impact type\n";
echo "2. Execute in dependency-safe order\n";
echo "3. Use parallel execution only within same category\n";
echo "4. Add inter-category delays for API safety\n";
echo "5. Implement rollback protection for conflicts\n";

echo "\n";
