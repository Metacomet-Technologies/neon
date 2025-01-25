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
    protected $signature = 'neon:start';
    protected $description = 'Run Neon';

    public function handle()
    {
        $this->components->info('Starting Neon...');

        // Save the current process ID (PID) to a file
        $pidFile = storage_path('app/neon.pid');
        file_put_contents($pidFile, getmypid());

        $log = new Logger('DiscordPHP');
        $log->pushHandler(new StreamHandler(storage_path('logs/neon.log'), Level::Info));

        $discord = new Discord([
            'token' => config('discord.token'),
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
            'logger' => $log,
        ]);

        $discord->on('init', function ($discord) {
            $this->components->info('Neon is running!');

            $discord->on(Event::MESSAGE_CREATE, function ($message) {
                if ($message->content === '!ping') {
                    $this->components->info('Received ping!');
                    $message->channel->sendMessage('Pong!');
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
}
