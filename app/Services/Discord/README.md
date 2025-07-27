# Discord Service

A unified Discord service for Laravel applications providing an expressive, fluent interface with built-in rate limiting, circuit breaker protection, and token management.

## Usage Examples

### Basic Usage

```php
use App\Services\Discord\DiscordService;

$discord = app(DiscordService::class);
// or
$discord = new DiscordService();

// Get a guild
$guild = $discord->guild('123456789');

// Get guild information
$guildInfo = $guild->get();

// Work with members
$member = $guild->member('987654321');
$member->addRole('roleId');
$member->setNickname('New Name');
$member->kick();

// Work with roles
$roles = $guild->roles()->get();
$adminRole = $guild->roles()->findByName('Admin');
$newRole = $guild->roles()->create(['name' => 'Moderator', 'color' => 0x00ff00]);

// Work with channels
$channels = $guild->channels()->get();
$textChannels = $guild->channels()->text()->get();
$newChannel = $guild->createChannel([
    'name' => 'general-chat',
    'type' => 0, // text channel
]);

// Send messages
$channel = $discord->channel('channelId');
$channel->send('Hello, world!');
$channel->send([
    'content' => 'Hello!',
    'embed' => [
        'title' => 'Welcome',
        'description' => 'Welcome to our server!',
        'color' => 0x00ff00,
    ],
]);
```

### Advanced Examples

```php
// Ban a user with message deletion
$guild->ban('userId', deleteMessageDays: 7);

// Find and modify a role
$role = $guild->roles()->findByName('Members');
if ($role) {
    $roleResource = $guild->roles()->find($role['id']);
    $roleResource->setColor(0xff0000);
    $roleResource->setHoist(true);
}

// Update channel permissions
$channel = $discord->channel('channelId');
$channel->editPermissions('roleId', [
    'allow' => 1024, // VIEW_CHANNEL
    'deny' => 2048,  // SEND_MESSAGES
], type: 0); // 0 = role, 1 = member

// Get member's highest role
$member = $guild->member('userId');
$highestPosition = $member->highestRolePosition();

// Timeout (mute) a member for 10 minutes
$member->timeout(new DateTime('+10 minutes'));

// Create channel with category
$channel = $guild->createChannel([
    'name' => 'support-ticket',
    'type' => 0,
    'parent_id' => 'categoryId',
    'topic' => 'Support ticket channel',
]);
```

### Comparison: Old vs New

**Old way (procedural):**
```php
$discordApi = new DiscordApiService();
$roles = $discordApi->getGuildRoles($guildId);
$role = $discordApi->findRoleByName($guildId, 'Admin');
$discordApi->assignRole($guildId, $userId, $roleId);
```

**New way (expressive):**
```php
$discord = app(DiscordService::class);
$guild = $discord->guild($guildId);
$roles = $guild->roles()->get();
$role = $guild->roles()->findByName('Admin');
$guild->member($userId)->addRole($roleId);
```

## Benefits

1. **Fluent Interface**: Chain methods for readable code
2. **Resource-Oriented**: Work with Discord entities as objects
3. **Type Safety**: Full IDE autocompletion and type hints
4. **Consistent API**: All resources follow similar patterns
5. **Easier Testing**: Mock individual resources
6. **Extensible**: Easy to add new resources and methods

## Additional Features

### Scheduled Events
```php
// Get all scheduled events
$events = $guild->scheduledEvents();

// Create a scheduled event
$event = $guild->createScheduledEvent([
    'name' => 'Community Meeting',
    'scheduled_start_time' => (new DateTime('+1 week'))->format('c'),
    'entity_type' => 2, // VOICE
    'channel_id' => 'voiceChannelId',
]);

// Delete a scheduled event
$guild->deleteScheduledEvent('eventId');
```

### Message Management
```php
$channel = $discord->channel('channelId');

// Get messages
$messages = $channel->getMessages(['limit' => 50]);

// Bulk delete messages
$channel->bulkDeleteMessages(['messageId1', 'messageId2']);

// Pin/unpin messages
$channel->pinMessage('messageId');
$channel->unpinMessage('messageId');
$pinnedMessages = $channel->getPinnedMessages();
```

### Voice Channel Management
```php
// Disconnect member from voice
$guild->disconnectMember('userId');
// or
$member->disconnectFromVoice();

// Lock/unlock voice channels
$channel->lockVoice($everyoneRoleId);
$channel->unlockVoice($everyoneRoleId);
```

### Channel Permissions & Moderation
```php
// Mute user in specific channels
$member->muteInChannels(['channelId1', 'channelId2']);
$member->unmuteInChannels(['channelId1', 'channelId2']);

// Lock/unlock text channels
$channel->lock($everyoneRoleId);
$channel->unlock($everyoneRoleId);

// Archive channel
$channel->archive(autoArchiveDuration: 1440); // 24 hours
```

### Bot Operations
```php
// Get all guilds the bot is in
$guilds = $discord->guilds();
```

### Guild Management
```php
// Set AFK channel and timeout
$guild->setAfkChannel('channelId', timeout: 300);

// Enable/disable boost progress bar
$guild->setBoostProgressBar(true);

// Prune inactive members
$result = $guild->pruneMembers(days: 7);
$count = $guild->getPruneCount(days: 7); // Dry run
```

### Channel Management Extensions
```php
$channel = $discord->channel('channelId');

// Vanish/unvanish channels
$channel->vanish($everyoneRoleId);
$channel->unvanish($everyoneRoleId);

// Set slowmode
$channel->setSlowmode(seconds: 10);

// Set NSFW status
$channel->setNsfw(true);

// Send polls
$channel->sendPoll(
    'What is your favorite color?',
    ['Red', 'Blue', 'Green', 'Yellow'],
    duration: 24,
    allowMultiselect: false
);
```

## Integration with Existing Code

The `DiscordService` combines the functionality of the previous `Discord` SDK and `DiscordApiService`, providing a unified interface with rate limiting, circuit breaker protection, and both resource-based and direct API methods.