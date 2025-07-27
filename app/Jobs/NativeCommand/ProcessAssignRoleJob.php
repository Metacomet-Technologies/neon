<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessAssignRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireRolePermission();

        // 2. Parse and validate input using service
        [$roleName, $userIds] = DiscordService::parseRoleCommand($this->messageContent, 'assign-role');

        if (! $roleName || empty($userIds)) {
            $this->sendUsageAndExample();
            throw new Exception('No role name or user IDs provided.', 400);
        }

        // 3. Find role by name using service
        $role = $this->getDiscord()->findRoleByName($this->guildId, $roleName);

        if (! $role) {
            $this->sendNotFound('Role', $roleName);
            throw new Exception('Role not found.', 404);
        }

        $roleId = $role['id'];

        // 4. Perform batch role assignment using service
        $results = $this->getDiscord()->batchOperation($userIds, function ($userId) use ($roleId) {
            return $this->getDiscord()->assignRole($this->guildId, $userId, $roleId);
        });

        // 5. Send batch results using trait
        $successfulUsers = array_map(fn ($userId) => "<@{$userId}>", $results['successful']);
        $failedUsers = array_map(fn ($userId) => "<@{$userId}>", $results['failed']);

        $this->sendBatchResults("Role '{$roleName}' assignment", $successfulUsers, $failedUsers);
    }
}
