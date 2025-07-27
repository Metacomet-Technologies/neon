<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessDisplayBoostJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireChannelPermission();

        // Extract boolean value from message
        $paramString = trim(str_replace('!display-boost', '', $this->messageContent));

        if (empty($paramString)) {
            $this->sendUsageAndExample();
            throw new Exception('Boolean value (true/false) is required.', 400);
        }

        // Validate boolean value
        $paramString = strtolower($paramString);
        if (! in_array($paramString, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'])) {
            $this->sendErrorMessage('Invalid value. Please use true/false, yes/no, on/off, or 1/0.');
            throw new Exception('Invalid boolean value.', 400);
        }

        $displayBoost = in_array($paramString, ['true', '1', 'yes', 'on']);

        $success = $this->getDiscord()->setGuildBoostProgressBar($this->guildId, $displayBoost);

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
