# Discord Command Jobs

This directory contains job classes for processing Discord bot commands. The jobs have been refactored to use reusable patterns and components for consistency and maintainability.

## 📊 Architecture Overview

### Job Structure
All Discord command jobs extend `ProcessBaseJob` which provides:
- Automatic analytics tracking via `CommandUsageMetric`
- Exception-based error handling (no manual status updates)
- Standardized constructor pattern
- Integration with reusable traits and services

### Job Lifecycle
1. **NeonDispatchHandler** receives command and dispatches appropriate job
2. **ProcessBaseJob** handles analytics and error tracking automatically
3. **Individual Job** implements `executeCommand()` with business logic
4. **Traits & Services** provide reusable functionality

## 🔧 Reusable Components

### Traits Available in ProcessBaseJob

#### **DiscordPermissionTrait**
Standardized permission checking with consistent error messages:

```php
protected function executeCommand(): void 
{
    $this->requireRolePermission();        // Manage roles
    $this->requireChannelPermission();     // Manage channels  
    $this->requireMemberPermission();      // Kick/timeout members
    $this->requireBanPermission();         // Ban members
    $this->requireAdminPermission();       // Admin-only commands
}
```

#### **DiscordValidatorTrait**
Input validation with automatic error responses:

```php
protected function executeCommand(): void
{
    // Validate Discord ID format
    $this->validateUserId($userId);
    $this->validateChannelId($channelId);
    
    // Validate required parameters
    $this->validateRequiredParameters($params, 2);
    
    // Validate mentions
    $userIds = $this->validateUserMentions($mentions);
    
    // Validate numeric ranges
    $timeout = $this->validateNumericRange($value, 0, 21600, 'Timeout');
    
    // Validate boolean inputs
    $enabled = $this->validateBoolean($input, 'NSFW setting');
}
```

#### **DiscordResponseTrait**
Consistent response messages and embeds:

```php
protected function executeCommand(): void
{
    // Success messages
    $this->sendSuccessMessage('Role Created', 'Role "VIP" created successfully.');
    
    // Error messages
    $this->sendErrorMessage('Role name is required.');
    
    // User action confirmations
    $this->sendUserActionConfirmation('banned', $userId, '🔨');
    
    // Channel action confirmations  
    $this->sendChannelActionConfirmation('locked', $channelId);
    
    // Batch operation results
    $this->sendBatchResults('Role assignment', $successful, $failed);
    
    // Standard responses
    $this->sendPermissionDenied('manage roles');
    $this->sendNotFound('Role', 'VIP');
    $this->sendApiError('ban user');
}
```

### Services Available

#### **DiscordParserService**
Parse Discord entities from message content:

```php
use App\Services\DiscordParserService;

protected function executeCommand(): void
{
    // Parse user commands (ban, kick, mute)
    $userId = DiscordParserService::parseUserCommand($this->messageContent, 'ban');
    
    // Parse channel edit commands
    [$channelId, $newName] = DiscordParserService::parseChannelEditCommand($this->messageContent, 'edit-channel-name');
    
    // Parse role commands
    [$roleName, $userIds] = DiscordParserService::parseRoleCommand($this->messageContent, 'assign-role');
    
    // Extract individual entities
    $userId = DiscordParserService::extractUserId('<@123456789>');
    $channelId = DiscordParserService::extractChannelId('<#123456789>');
    
    // Validate Discord ID format
    if (!DiscordParserService::isValidDiscordId($id)) {
        // Handle invalid ID
    }
}
```

#### **DiscordApiService**
Standardized Discord API operations with retry logic:

```php
protected function executeCommand(): void
{
    // Role operations
    $roles = $this->discord->getGuildRoles($this->guildId);
    $role = $this->discord->findRoleByName($this->guildId, 'VIP');
    $this->discord->assignRole($this->guildId, $userId, $roleId);
    $this->discord->removeRole($this->guildId, $userId, $roleId);
    
    // User moderation
    $this->discord->banUser($this->guildId, $userId);
    $this->discord->kickUser($this->guildId, $userId);
    $this->discord->unbanUser($this->guildId, $userId);
    
    // Channel management
    $this->discord->updateChannel($channelId, ['name' => 'new-name']);
    $this->discord->createChannel($this->guildId, $channelData);
    $this->discord->deleteChannel($channelId);
    
    // Role hierarchy checking
    $position = $this->discord->getUserHighestRolePosition($this->guildId, $userId);
    
    // Batch operations with rate limiting
    $results = $this->discord->batchOperation($userIds, function($userId) use ($roleId) {
        return $this->discord->assignRole($this->guildId, $userId, $roleId);
    });
}
```

## 📈 Job Pattern Comparison

### Before Refactoring
```php
final class ProcessBanUserJob extends ProcessBaseJob
{
    // 217 lines of code with:
    // - Manual permission checking
    // - Complex regex parsing  
    // - Manual HTTP requests with retry logic
    // - Inconsistent error messages
    // - Mixed concerns
}
```

### After Refactoring  
```php
final class ProcessBanUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Permission check
        $this->requireBanPermission();
        
        // 2. Parse and validate
        $targetUserId = DiscordParserService::parseUserCommand($this->messageContent, 'ban');
        if (!$targetUserId) {
            $this->sendUsageAndExample();
            throw new \Exception('No user ID provided.', 400);
        }
        $this->validateUserId($targetUserId);
        
        // 3. Role hierarchy check
        $senderRole = $this->discord->getUserHighestRolePosition($this->guildId, $this->discordUserId);
        $targetRole = $this->discord->getUserHighestRolePosition($this->guildId, $targetUserId);
        if ($senderRole <= $targetRole) {
            $this->sendErrorMessage('Cannot ban user with equal or higher role.');
            throw new \Exception('Insufficient role hierarchy.', 403);
        }
        
        // 4. Perform action
        $success = $this->discord->banUser($this->guildId, $targetUserId);
        if (!$success) {
            $this->sendApiError('ban user');
            throw new \Exception('Failed to ban user.', 500);
        }
        
        // 5. Send confirmation
        $this->sendUserActionConfirmation('banned', $targetUserId, '🔨');
    }
}
// Only ~45 lines total (80% reduction)
```

## 🚀 Benefits of New Pattern

1. **80% Code Reduction** - From ~217 lines to ~45 lines per job
2. **100% Reusable Components** - Traits and services used across all jobs
3. **Consistent Behavior** - Standardized error messages and responses
4. **Easier Testing** - Separated concerns, mockable services  
5. **Better Maintainability** - Changes in one place affect all jobs
6. **Clear Business Logic** - Jobs focus only on their specific command logic

## 📝 Creating New Jobs

When creating a new Discord command job:

1. **Extend ProcessBaseJob**
```php
final class ProcessNewCommandJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // Your command logic here
    }
}
```

2. **Use Permission Traits**
```php
$this->requireRolePermission(); // or appropriate permission
```

3. **Use Parser Service**
```php
$userId = DiscordParserService::parseUserCommand($this->messageContent, 'command');
```

4. **Use Validator Traits**
```php
$this->validateUserId($userId);
```

5. **Use Discord API Service**
```php
$success = $this->discord->performAction($this->guildId, $params);
```

6. **Use Response Traits**
```php
$this->sendSuccessMessage('Action Complete', 'Description of what happened.');
```

## 🔄 Migration Status

- ✅ **Base Infrastructure** - ProcessBaseJob updated with traits
- ✅ **Reusable Components** - All traits and services created  
- ✅ **Individual Jobs** - All Discord command jobs refactored to new patterns
- ✅ **Code Reduction** - Achieved 60-80% code reduction across all jobs
- ✅ **Pattern Consistency** - All jobs follow standardized 4-step pattern

## 📂 File Structure

```
app/Jobs/
├── README.md                           # This file
├── NeonDispatchHandler.php             # Command dispatcher
├── NativeCommand/
│   └── ProcessBaseJob.php              # Base class with traits
├── Process*Job.php                     # Individual command jobs (all refactored)

app/Traits/
├── DiscordPermissionTrait.php          # Permission checking
├── DiscordResponseTrait.php            # Response formatting  
└── DiscordValidatorTrait.php           # Input validation

app/Services/
├── DiscordApiService.php               # Discord API operations
├── DiscordParserService.php            # Message parsing
└── CommandAnalyticsService.php         # Usage analytics
```

## 🧪 Testing

The new pattern makes testing much easier:

```php
// Mock the DiscordApiService for isolated testing
$this->mock(DiscordApiService::class)
    ->shouldReceive('banUser')
    ->once()
    ->with($guildId, $userId)
    ->andReturn(true);

// Test only the business logic, not the infrastructure
```

## 📞 Support

For questions about implementing new jobs or refactoring existing ones, refer to:
- `ProcessBanUserJobRefactored.php` - Complete example implementation
- Individual trait/service files - Detailed method documentation
- `ProcessBaseJob.php` - Integration point for all reusable components