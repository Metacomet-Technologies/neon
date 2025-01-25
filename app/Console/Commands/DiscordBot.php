<?php

namespace App\Console\Commands;

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;

class DiscordBot extends Command
{
    protected $signature = 'bot:run';
    protected $description = 'Run the Discord bot';

    public function handle()
    {
        $this->info('Starting Discord bot...');

        $discord = new Discord([
            'token' => env('DISCORD_BOT_TOKEN'),
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
        ]);

        $discord->on('ready', function ($discord) {
            $this->info("Bot is ready!");

            // Respond to messages
            $discord->on(Event::MESSAGE_CREATE, function ($message) use ($discord) {
                if ($message->content === '!ping') {
                    $message->channel->sendMessage('Pong!');
                }
            });
        });

        $discord->run();
    }
}
