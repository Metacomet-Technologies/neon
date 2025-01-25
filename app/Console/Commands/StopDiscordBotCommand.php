<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class StopDiscordBotCommand extends Command
{
    protected $signature = 'bot:stop';
    protected $description = 'Stop the Discord bot process';

    public function handle()
    {
        $this->info('Stopping Discord bot...');

        // Locate the PID file
        $pidFile = storage_path('app/discord-bot.pid');

        if (! file_exists($pidFile)) {
            $this->error('No PID file found. Is the bot running?');

            return 1;
        }

        $pid = file_get_contents($pidFile);

        // Determine the operating system
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Use proc_open for taskkill
            $process = proc_open(
                ['taskkill', '/F', '/PID', $pid],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            $resultCode = proc_close($process);
        } else {
            // Linux/Unix: Use posix_kill
            if (function_exists('posix_kill')) {
                $resultCode = posix_kill((int) $pid, SIGTERM) ? 0 : 1;
            } else {
                $this->error('posix_kill is not available on this system.');

                return 1;
            }
        }

        if ($resultCode === 0) {
            $this->info("Bot process with PID {$pid} has been stopped.");
            unlink($pidFile); // Remove the PID file
        } else {
            $this->error("Failed to stop the bot process with PID {$pid}. Check permissions or ensure the process is running.");

            return 1;
        }

        return 0;
    }
}
