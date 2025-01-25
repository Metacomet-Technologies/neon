<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'neon:stop')]
final class StopNeonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neon:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the Neon process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->components->info('Stopping Neon...');

        // Locate the PID file
        $pidFile = storage_path('app/neon.pid');

        if (! file_exists($pidFile)) {
            $this->components->error('No PID file found. Is Neon running?');

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
                $this->components->error('posix_kill is not available on this system.');

                return 1;
            }
        }

        if ($resultCode === 0) {
            $this->components->info('Broadcasting Neon stop signal.');
            unlink($pidFile); // Remove the PID file
        } else {
            $this->components->error("Failed to stop the Neon process with PID {$pid}. Check permissions or ensure Neon is running.");

            return 1;
        }

        return 0;
    }
}
