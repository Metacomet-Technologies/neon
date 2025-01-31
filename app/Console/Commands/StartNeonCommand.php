<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessAssignRoleJob;
use App\Jobs\ProcessDeleteRoleJob;
use App\Jobs\ProcessGuildCommandJob;
use App\Jobs\ProcessNewCategoryJob;
use App\Jobs\ProcessNewChannelJob;
use App\Jobs\ProcessNewEventJob;
use App\Jobs\ProcessNewRoleJob;
use App\Jobs\ProcessRemoveRoleJob;
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
    /**
     * The environment to use.
     */
    public string $environment;

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
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->environment = config('app.env');
    }

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
                $commands = $this->getCommandsForGuild($guildId);
                $channelId = $message->channel->id;
                foreach ($commands as $command) {
                    $parts = explode(' ', $message->content);
                    if ($parts[0] === '!' . $command['command']) {
                        ProcessGuildCommandJob::dispatch($guildId, $channelId, $command, $message->content);
                    }
                }

                if (str_starts_with($message->content, '!new-channel')) {
                    ProcessNewChannelJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }
                if (str_starts_with($message->content, '!new-category')) {
                    ProcessNewCategoryJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
                }
                if (str_starts_with($message->content, '!assign-role')) {
                    ProcessAssignRoleJob::dispatch($message->channel->id, $message->channel->guild_id, $message->content);
                }
                if (str_starts_with($message->content, '!add-role')) {
                    ProcessNewRoleJob::dispatch(
                        $message->author->id,   // ✅ Add the Discord user ID
                        $message->channel->id,
                        $message->channel->guild_id,
                        $message->content
                    );
                }
                if (str_starts_with($message->content, '!delete-role')) {
                    ProcessDeleteRoleJob::dispatch($message->channel->id, $message->channel->guild_id, $message->content);
                }
                if (str_starts_with($message->content, '!remove-role')) {
                    ProcessRemoveRoleJob::dispatch($message->channel->id, $message->channel->guild_id, $message->content);
                }
                if (str_starts_with($message->content, '!create-event')) {
                    ProcessNewEventJob::dispatch($message->author->id, $channelId, $guildId, $message->content);
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
     * Get the commands for a guild.
     *
     * @return array<string, mixed>
     */
    public function getCommandsForGuild(string $guildId): array
    {
        $key = 'guild-commands:' . $guildId;

        return Cache::rememberForever($key, function () use ($guildId) {
            return NeonCommand::query()
                ->aciveGuildCommands($guildId)
                ->get()
                ->toArray();
        });
    }

    /**
     * Set the message output based on the environment.
     */
    private function setMessageOutput(string $message): string
    {
        if ($this->environment === 'production') {
            return $message;
        }

        return '[' . $this->environment . '] ' . $message;
    }
}
