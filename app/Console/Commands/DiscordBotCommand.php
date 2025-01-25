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

final class DiscordBotCommand extends Command
{
    protected $signature = 'bot:run';
    protected $description = 'Run the Discord bot';

    public function handle()
    {
        $this->info('Starting Discord bot...');

        // Save the current process ID (PID) to a file
        $pidFile = storage_path('app/discord-bot.pid');
        file_put_contents($pidFile, getmypid());

        $log = new Logger('DiscordPHP');
        $log->pushHandler(new StreamHandler(storage_path('logs/discord.log'), Level::Info));

        $discord = new Discord([
            'token' => config('discord.token'),
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
            'logger' => $log,
        ]);

        $discord->on('ready', function ($discord) {
            $this->info('Bot is ready!');

            $discord->on(Event::MESSAGE_CREATE, function ($message) {
                if ($message->content === '!ping') {
                    $message->channel->sendMessage('Pong!');
                }
            });
        });

        $discord->run();

        // Clean up PID file when the bot stops
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }
}
