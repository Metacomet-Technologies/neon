<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessAssignChannelJob;
use App\Jobs\ProcessAssignRoleJob;
use App\Jobs\ProcessDeleteCategoryJob;
use App\Jobs\ProcessDeleteChannelJob;
use App\Jobs\ProcessEditChannelJob;
use App\Jobs\ProcessEditChannelNameJob;
use App\Jobs\ProcessEditChannelTopicJob;
use App\Jobs\ProcessGuildCommandJob;
use App\Jobs\ProcessLockChannelJob;
use App\Jobs\ProcessNewCategoryJob;
use App\Jobs\ProcessNewChannelJob;
use App\Jobs\ProcessNewEventJob;
use App\Models\NeonCommand;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;
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

                // Restore dynamic command execution
                $commands = $this->getCommandsForGuild($guildId);
                foreach ($commands as $command) {
                    $parts = explode(' ', $message->content);
                    if ($parts[0] === '!' . $command['command']) {
                        ProcessGuildCommandJob::dispatch($guildId, $channelId, $command, $message->content);

                        return;
                    }
                }

                // Hardcoded command checks remain
                if (str_starts_with($message->content, '!new-channel ')) {
                    ProcessNewChannelJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!assign-channel ')) {
                    ProcessAssignChannelJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!delete-channel ')) {
                    ProcessDeleteChannelJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!edit-channel ')) {
                    ProcessEditChannelJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!edit-channel-name ')) {
                    ProcessEditChannelNameJob::dispatch($channelId, $message->content);
                }

                if (str_starts_with($message->content, '!edit-channel-topic ')) {
                    ProcessEditChannelTopicJob::dispatch($channelId, $message->content);
                }

                if (str_starts_with($message->content, '!lock-channel ')) {
                    $args = explode(' ', $message->content);
                    $channelId = $args[1] ?? null;

                    if (! $channelId) {
                        $message->reply('Please provide a valid channel ID.');

                        return;
                    }

                    ProcessLockChannelJob::dispatch($message->author->id, $channelId, $message->guild_id, $message->content);
                }

                if (str_starts_with($message->content, '!new-category ')) {
                    ProcessNewCategoryJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!delete-category ')) {
                    ProcessDeleteCategoryJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!assign-role ')) {
                    ProcessAssignRoleJob::dispatch($message->channel->id, $guildId, $message->content);
                }

                if (str_starts_with($message->content, '!create-event ')) {
                    ProcessNewEventJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
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

    private function setMessageOutput(string $message): string
    {
        if ($this->environment === 'production') {
            return $message;
        }

        return '[' . $this->environment . '] ' . $message;
    }
}
