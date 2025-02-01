<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessGuildCommandJob;
use App\Models\NativeCommand;
use App\Models\NeonCommand;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'neon:start')]
final class StartNeonCommand extends Command
{
    public string $environment;

    protected $signature = 'neon:start';
    protected $description = 'Run Neon';

    public function __construct()
    {
        parent::__construct();
        $this->environment = config('app.env');
    }

    public function handle(): void
    {
        $this->components->info('Starting Neon...');

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

        $discord->on('init', function ($discord) {
            $this->components->info('Neon is running!');

            $discord->on(Event::MESSAGE_CREATE, function ($message, $discord) {
                if ($message->author->bot) {
                    return;
                }

                if (! str_starts_with($message->content, '!')) {
                    return;
                }

                $guildId = $message->channel->guild_id;
                $channelId = $message->channel->id;
                $discordUserId = $message->author->id;
                $messageContent = $message->content;

                // Restore dynamic command execution
                $commands = $this->getCommandsForGuild($guildId);
                foreach ($commands as $command) {
                    $parts = explode(' ', $message->content);
                    if ($parts[0] === '!' . $command['command']) {
                        ProcessGuildCommandJob::dispatch($discordUserId, $channelId, $guildId, $messageContent, $command);

                        return;
                    }
                }

                $nativeCommands = $this->getNativeCommands();
                foreach ($nativeCommands as $command) {
                    $parts = explode(' ', $messageContent);
                    $commandSlug = $parts[0];
                    if ($commandSlug === '!' . $command['slug']) {
                        Bus::dispatch(new $command['class']($discordUserId, $channelId, $guildId, $messageContent));

                        return;
                    }
                }
            });
        });

        $discord->run();

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    public function getCommandsForGuild(string $guildId): array
    {
        $key = 'guild-commands:' . $guildId;

        return Cache::rememberForever($key, function () use ($guildId) {
            return NeonCommand::query()
                ->where('guild_id', $guildId)
                ->where('is_enabled', true)
                ->get()
                ->toArray();
        });
    }

    public function getNativeCommands(): array
    {
        $key = 'native-commands';

        return Cache::rememberForever($key, function () {
            return NativeCommand::query()
                ->where('is_active', true)
                ->get()
                ->toArray();
        });
    }
}
