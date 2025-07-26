<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessDisplayBoostJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireChannelPermission();

        $params = Discord::extractParameters($this->messageContent, 'display-boost');
        $this->validateRequiredParameters($params, 1, 'Boolean value (true/false) is required.');

        $displayBoost = $this->validateBoolean($params[0], 'display boost setting');

        $success = $this->discord->setGuildBoostProgressBar($this->guildId, $displayBoost);

        if (! $success) {
            $this->sendApiError('update boost progress bar');
            throw new Exception('Failed to update boost progress bar.', 500);
        }

        $status = $displayBoost ? 'enabled' : 'disabled';
        $statusIcon = $displayBoost ? '✅' : '❌';
        $color = $displayBoost ? 3447003 : 15158332;

        $this->sendSuccessMessage(
            '🚀 Boost Progress Bar Updated',
            "{$statusIcon} Server Boost Progress Bar is now **{$status}**.",
            $color
        );
    }
}
