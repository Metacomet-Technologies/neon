<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;

final class ProcessNewRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        $params = Discord::extractParameters($this->messageContent, 'new-role');
        $this->validateRequiredParameters($params, 1, 'Role name is required.');

        $roleName = $params[0];
        $color = isset($params[1]) ? $this->parseColorValue($params[1]) : 0xFFFFFF;
        $hoist = isset($params[2]) ? $this->validateBoolean($params[2], 'hoist') : false;

        // Check if role already exists
        $existingRole = $this->discord->findRoleByName($this->guildId, $roleName);
        if ($existingRole) {
            $this->sendErrorMessage("Role '{$roleName}' already exists.");
            throw new Exception('Role already exists.', 409);
        }

        $roleData = ['name' => $roleName, 'color' => $color, 'hoist' => $hoist];
        $role = $this->discord->createRole($this->guildId, $roleData);

        if (! $role) {
            $this->sendApiError('create role');
            throw new Exception('Failed to create role.', 500);
        }

        $colorHex = '#' . strtoupper(str_pad(dechex($role['color']), 6, '0', STR_PAD_LEFT));
        $hoistText = $role['hoist'] ? '✅ Yes' : '❌ No';

        $this->sendSuccessMessage(
            'Role Created!',
            "**Role Name:** {$role['name']}\n**Color:** {$colorHex}\n**Displayed Separately:** {$hoistText}",
            $role['color']
        );
    }

    /**
     * Parse color value from string input.
     */
    private function parseColorValue(string $input): int
    {
        // Remove # if present and validate hex format
        $cleanHex = ltrim($input, '#');

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $cleanHex)) {
            $this->sendErrorMessage('Invalid color format. Use hex format like #FF0000 or FF0000.');
            throw new Exception('Invalid color format.', 400);
        }

        return hexdec($cleanHex);
    }
}
