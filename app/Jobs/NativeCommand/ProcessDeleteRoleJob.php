<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessDeleteRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        $params = Discord::extractParameters($this->messageContent, 'delete-role');
        $this->validateRequiredParameters($params, 1, 'Role name is required.');

        $roleName = $params[0];
        $role = $this->discord->findRoleByName($this->guildId, $roleName);
        $this->validateTarget($role, 'Role', $roleName);

        $success = $this->discord->deleteRole($this->guildId, $role['id']);

        if (! $success) {
            $this->sendApiError('delete role');
            throw new Exception('Failed to delete role.', 500);
        }

        $this->sendRoleActionConfirmation('deleted', $roleName);
    }
}
