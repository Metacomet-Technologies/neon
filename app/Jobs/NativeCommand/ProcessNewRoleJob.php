<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessNewRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        // Extract parameters from message
        $paramString = trim(str_replace('!new-role', '', $this->messageContent));

        if (empty($paramString)) {
            $this->sendUsageAndExample();
            throw new Exception('Role name is required.', 400);
        }

        // Parse parameters - format: roleName [color] [hoist]
        $params = explode(' ', $paramString);
        $roleName = $params[0];
        $color = isset($params[1]) ? $this->parseColorValue($params[1]) : 0xFFFFFF;

        // Parse hoist parameter if provided
        $hoist = false;
        if (isset($params[2])) {
            $hoistValue = strtolower($params[2]);
            if (! in_array($hoistValue, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'])) {
                $this->sendErrorMessage('Invalid hoist value. Please use true/false, yes/no, on/off, or 1/0.');
                throw new Exception('Invalid boolean value for hoist.', 400);
            }
            $hoist = in_array($hoistValue, ['true', '1', 'yes', 'on']);
        }

        // Check if role already exists
        $existingRole = $this->getDiscord()->findRoleByName($this->guildId, $roleName);
        if ($existingRole) {
            $this->sendErrorMessage("Role '{$roleName}' already exists.");
            throw new Exception('Role already exists.', 409);
        }

        $roleData = ['name' => $roleName, 'color' => $color, 'hoist' => $hoist];
        $role = $this->getDiscord()->createRole($this->guildId, $roleData);

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
