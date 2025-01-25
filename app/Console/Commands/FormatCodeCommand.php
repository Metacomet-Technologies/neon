<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'code:format')]
final class FormatCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:format
                            {--p|php : Run PHP file formatters}
                            {--j|js : Run JS file formatters}
                            {--l|larastan : Run Larastan}
                            {--t|test : Run tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Opinionated code formatter';

    /**
     * The commands to run for each option.
     *
     * @var array<string, array<int, array{name: string, command: string}>>
     */
    private array $commands = [
        'php' => [
            [
                'name' => 'ide-helper for Eloquent',
                'command' => 'php artisan ide-helper:eloquent',
            ],
            [
                'name' => 'ide-helper for Laravel',
                'command' => 'php artisan ide-helper:generate',
            ],
            [
                'name' => 'ide-helper for Models',
                'command' => 'php artisan ide-helper:models -W -R',
            ],
            [
                'name' => 'duster fix',
                'command' => 'php ./vendor/bin/duster fix',
            ],
        ],
        'js' => [
            [
                'name' => 'prettier',
                'command' => 'prettier --write "resources/js/**/*.{ts,tsx}"',
            ],
            [
                'name' => 'vite build',
                'command' => 'npm run build',
            ],
        ],
        'larastan' => [
            [
                'name' => 'larastan for static analysis',
                'command' => 'php ./vendor/bin/phpstan analyse --memory-limit=2G',
            ],
        ],
        'test' => [
            [
                'name' => 'pest for testing',
                'command' => 'php ./vendor/bin/pest',
            ],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $commandsToRun = [];
        if ($this->option('php')) {
            $commandsToRun = array_merge($commandsToRun, $this->commands['php']);
        }
        if ($this->option('js')) {
            $commandsToRun = array_merge($commandsToRun, $this->commands['js']);
        }
        if ($this->option('larastan')) {
            $commandsToRun = array_merge($commandsToRun, $this->commands['larastan']);
        }
        if ($this->option('test')) {
            $commandsToRun = array_merge($commandsToRun, $this->commands['test']);
        }

        // if no options are passed, run all commands
        if (empty($commandsToRun)) {
            $commandsToRun = array_merge(...array_values($this->commands));
        }

        $this->withProgressBar($commandsToRun, function ($command) {
            $this->line('');
            $this->components->info('Starting ' . $command['name']);

            $process = Process::fromShellCommandline($command['command']);
            $process->run();

            $this->info($process->getOutput());

            $this->components->info('Finished ' . $command['name']);
        });
    }
}
