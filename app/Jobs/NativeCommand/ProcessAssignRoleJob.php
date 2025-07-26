<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessAssignRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireRolePermission();

        // 2. Parse and validate input using service
        [$roleName, $userIds] = Discord::parseRoleCommand($this->messageContent, 'assign-role');

        if (! $roleName || empty($userIds)) {
            $this->sendUsageAndExample();
            throw new Exception('No role name or user IDs provided.', 400);
        }

        // 3. Find role by name using service
        $role = $this->discord->findRoleByName($this->guildId, $roleName);

        if (! $role) {
            $this->sendNotFound('Role', $roleName);
            throw new Exception('Role not found.', 404);
        }

        $roleId = $role['id'];

        // 4. Perform batch role assignment using service
        $results = $this->discord->batchOperation($userIds, function ($userId) use ($roleId) {
            return $this->discord->assignRole($this->guildId, $userId, $roleId);
        });

        // 5. Send batch results using trait
        $successfulUsers = array_map(fn ($userId) => "<@{$userId}>", $results['successful']);
        $failedUsers = array_map(fn ($userId) => "<@{$userId}>", $results['failed']);

        $this->sendBatchResults("Role '{$roleName}' assignment", $successfulUsers, $failedUsers);
    }
}
