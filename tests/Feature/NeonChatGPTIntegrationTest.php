<?php

declare(strict_types=1);

use App\Jobs\ProcessNeonChatGPTJob;
use App\Models\NativeCommandRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('has neon command in database', function () {
    $this->artisan('db:seed', ['--class' => 'NativeCommandSeeder']);
    
    $this->assertDatabaseHas('native_commands', [
        'slug' => 'neon',
        'is_active' => true,
    ]);
});

it('can parse user query', function () {
    $request = NativeCommandRequest::create([
        'guild_id' => '123456789',
        'channel_id' => '987654321',
        'discord_user_id' => '555666777',
        'message_content' => '!neon show me all users',
        'command' => ['slug' => 'neon'],
        'status' => 'pending',
    ]);

    $job = new ProcessNeonChatGPTJob($request);
    
    // Use reflection to test the private parseMessage method
    $reflection = new \ReflectionClass($job);
    $method = $reflection->getMethod('parseMessage');
    $method->setAccessible(true);
    
    $result = $method->invoke($job, '!neon show me all users');
    
    expect($result)->toBe('show me all users');
});

it('handles empty query correctly', function () {
    Http::fake([
        'discord.com/*' => Http::response(['id' => '123'], 200),
    ]);

    $request = NativeCommandRequest::create([
        'guild_id' => '123456789',
        'channel_id' => '987654321',
        'discord_user_id' => '555666777',
        'message_content' => '!neon',
        'command' => [
            'slug' => 'neon',
            'usage' => 'Usage: !neon <your question>',
            'example' => 'Example: !neon show me all users'
        ],
        'status' => 'pending',
    ]);

    $job = new ProcessNeonChatGPTJob($request);
    $job->handle();

    $request->refresh();
    expect($request->status)->toBe('failed');
});

it('validates sql safety correctly', function () {
    $reflection = new \ReflectionClass(\App\Jobs\ProcessNeonSQLExecutionJob::class);
    $method = $reflection->getMethod('isSafeQuery');
    $method->setAccessible(true);
    
    $job = new \App\Jobs\ProcessNeonSQLExecutionJob('123', '456', true);

    // Test safe queries
    expect($method->invoke($job, 'SELECT * FROM users WHERE id = 1'))->toBeTrue();
    expect($method->invoke($job, 'UPDATE users SET name = "test" WHERE id = 1'))->toBeTrue();
    expect($method->invoke($job, 'INSERT INTO users (name) VALUES ("test")'))->toBeTrue();

    // Test unsafe queries
    expect($method->invoke($job, 'DROP TABLE users'))->toBeFalse();
    expect($method->invoke($job, 'DELETE FROM users'))->toBeFalse();
    expect($method->invoke($job, 'TRUNCATE users'))->toBeFalse();
    expect($method->invoke($job, 'ALTER TABLE users ADD COLUMN test VARCHAR(255)'))->toBeFalse();
});

it('handles cache expiry properly', function () {
    $channelId = '123456789';
    $userId = '987654321';
    $cacheKey = "neon_sql_{$channelId}_{$userId}";
    
    // Test that commands are cached
    Cache::put($cacheKey, ['SELECT * FROM users'], now()->addMinutes(5));
    expect(Cache::has($cacheKey))->toBeTrue();
    
    // Test cache retrieval
    $commands = Cache::get($cacheKey);
    expect($commands)->toBeArray();
    expect($commands)->toContain('SELECT * FROM users');
    
    // Test cache expiry
    Cache::forget($cacheKey);
    expect(Cache::has($cacheKey))->toBeFalse();
});

it('caches database schema', function () {
    // This test verifies that database schema is properly cached
    $cacheKey = 'neon_db_schema';
    
    // Clear any existing cache
    Cache::forget($cacheKey);
    
    // The schema should be cached after first access
    $job = new ProcessNeonChatGPTJob(
        NativeCommandRequest::create([
            'guild_id' => '123456789',
            'channel_id' => '987654321',
            'discord_user_id' => '555666777',
            'message_content' => '!neon test',
            'command' => ['slug' => 'neon'],
            'status' => 'pending',
        ])
    );

    $reflection = new \ReflectionClass($job);
    $method = $reflection->getMethod('getDatabaseSchema');
    $method->setAccessible(true);
    
    $schema = $method->invoke($job);
    
    expect($schema)->toBeArray();
    expect(Cache::has($cacheKey))->toBeTrue();
});
