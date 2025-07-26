<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommand;
use Exception;

final class ProcessHelpCommandJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // No permission check needed for help

        $commands = $this->getActiveCommands();

        if ($commands->isEmpty()) {
            $this->sendErrorMessage('No commands are currently available.');
            throw new Exception('No commands available.', 404);
        }

        $commandList = $commands->map(function ($cmd) {
            return "**!{$cmd->slug}** - {$cmd->description}";
        })->toArray();

        $this->sendListMessage('Available Commands', $commandList);
    }

    /**
     * Get active commands from the database.
     */
    private function getActiveCommands()
    {
        return NativeCommand::where('is_active', true)
            ->select('slug', 'description')
            ->orderBy('slug')
            ->get();
    }
}
