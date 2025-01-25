<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'neon:start')]
final class StartNeonCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'neon:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Neon';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->components->info('Starting Neon...');

        // Save the current process ID (PID) to a file
        $pidFile = storage_path('app/neon.pid');
        file_put_contents($pidFile, getmypid());

        $log = new Logger('DiscordPHP');
        $log->pushHandler(new StreamHandler(storage_path('logs/neon.log'), Level::Info));

        $token = config('discord.token');

        $discord = new Discord([
            'token' => $token,
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
            'logger' => $log,
        ]);

        $env = config('app.env');

        $discord->on('init', function ($discord) use ($env) {
            $this->components->info('Neon is running!');

            $discord->on(Event::MESSAGE_CREATE, function ($message) use ($env) {
                if ($message->content === '!ping') {
                    $this->components->info('Received ping!');
                    $message->channel->sendMessage($this->setMessageOutput('pong!', $env));
                    $this->components->info('Sent pong!');
                }
            });
        });

        $discord->run();

        // Clean up PID file when the neon stops
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * Set the message output based on the environment.
     */
    private function setMessageOutput(string $message, string $environment = 'production'): string
    {
        if ($environment === 'production') {
            return $message;
        }

        return '[' . $environment . '] ' . $message;
    }
}
