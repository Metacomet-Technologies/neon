<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessDeleteRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        // Extract role name from message
        $roleName = trim(str_replace('!delete-role', '', $this->messageContent));

        if (empty($roleName)) {
            $this->sendUsageAndExample();
            throw new Exception('Role name is required.', 400);
        }
        $role = $this->getDiscord()->findRoleByName($this->guildId, $roleName);

        if (! $role) {
            $this->sendErrorMessage("Role '{$roleName}' not found.");
            throw new Exception('Role not found.', 404);
        }

        $success = $this->getDiscord()->deleteRole($this->guildId, $role['id']);

        if (! $success) {
            $this->sendApiError('delete role');
            throw new Exception('Failed to delete role.', 500);
        }

        $this->sendSuccessMessage(
            'Role Deleted',
            "🗑️ Role **{$roleName}** has been successfully deleted.",
            15158332 // Red
        );
    }
}
