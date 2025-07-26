<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;

final class ProcessDeleteRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        $params = DiscordParserService::extractParameters($this->messageContent, 'delete-role');
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
