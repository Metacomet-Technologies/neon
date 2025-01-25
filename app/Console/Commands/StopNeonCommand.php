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
    public function handle(): int
    {
        $this->components->info('Stopping Neon...');

        // Locate the PID file
        $pidFile = storage_path('app/neon.pid');

        if (! file_exists($pidFile)) {
            $this->components->error('No PID file found. Is Neon running?');

            return 1;
        }

        $pid = file_get_contents($pidFile);

        if ($pid === false) {
            $this->components->error('Failed to read PID file.');

            return 1;
        }

        // Determine the operating system
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Use proc_open for taskkill
            $process = proc_open(
                ['taskkill', '/F', '/PID', $pid],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            // if $process is false, the command failed
            if ($process === false) {
                $this->components->error('Failed to execute taskkill command.');

                return 1;
            }

            $resultCode = proc_close($process);

            if ($resultCode !== 0) {
                $this->components->error('Failed to stop Neon process.');

                return 1;
            }
        } else {
            // Unix-based systems: Use posix_kill
            if (! posix_kill((int) $pid, SIGTERM)) {
                $this->components->error('Failed to stop Neon process.');

                return 1;
            }
        }

        // Remove the PID file
        unlink($pidFile);

        $this->components->info('Neon stopped successfully.');

        return 0;
    }
}
