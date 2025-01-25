<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\DiscordChannelValidator;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
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

                if (str_starts_with($message->content, '!new-channel')) {
                    $this->handleCreateChannel($message, $discord);
                }

                if ($message->content === '!ping') {
                    $this->handlePing($message);
                }

                if (str_starts_with($message->content, '!assign-role')) {
                    $this->handleAssignRole($message, $discord);
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
    private function setMessageOutput(string $message): string
    {
        if ($this->environment === 'production') {
            return $message;
        }

        return '[' . $this->environment . '] ' . $message;
    }

    private function handleCreateChannel(mixed $message, mixed $discord): void
    {
        $usage = 'Usage: !new-channel <channel-name> <channel-type>';

        $example = 'Example: !new-channel test-channel text';

        $this->components->info('Received create channel request!');

        // Get the author ID from the message
        $authorId = $message->author->id;

        // check if the author has the permission to create channels
        $member = $message->channel->guild->members->get('user_id', $authorId);
        if (! $member) {
            $message->channel->sendMessage($this->setMessageOutput('You are not allowed to create channels.'));
            $this->components->error('User is not allowed to create channels.');

            return;
        }

        // Check if the user has the permission to create channels
        if (! $member->hasPermission('manage_channels')) {
            $message->channel->sendMessage($this->setMessageOutput('You are not allowed to create channels.'));
            $this->components->error('User is not allowed to create channels.');

            return;
        }

        // Check if the message content contains 3 parts
        $parts = explode(' ', $message->content);
        if (count($parts) < 2) {
            $message->channel->sendMessage($this->setMessageOutput($usage));
            $message->channel->sendMessage($this->setMessageOutput($example));

            return;
        }

        // Extract the channel name from the message content
        $channelName = $parts[1];

        // Validate the channel name is valid
        $validationResult = DiscordChannelValidator::validateChannelName($channelName);
        if (! $validationResult['is_valid']) {
            $message->channel->sendMessage($this->setMessageOutput($validationResult['message']));

            return;
        }

        // Extract the channel type from the message content if not provided
        // Default to 'text' if not provided
        $channelType = $parts[2] ?? 'text';

        // If the channel type is not text or voice, send an error message
        if (! in_array($channelType, ['text', 'voice'])) {
            $message->channel->sendMessage($this->setMessageOutput('Invalid channel type. Please use "text" or "voice".'));

            return;
        }

        // Get the guild ID from the message
        $guildId = $message->channel->guild_id;

        // Get the guild from the Discord client
        /** @var \Discord\Parts\Guild\Guild $guild */
        $guild = $discord->guilds->get('id', $guildId);

        // If the guild is not found, send an error message
        if (! $guild) {
            $message->channel->sendMessage($this->setMessageOutput('Server not found.'));

            return;
        }

        // Create the new channel object
        $newChannel = new \Discord\Parts\Channel\Channel($discord, [
            'name' => $channelName,
            'type' => $channelType === 'text' ? Channel::TYPE_GUILD_TEXT : Channel::TYPE_GUILD_VOICE,
        ]);

        // Save the new channel to the guild
        $guild->channels->save($newChannel)
            ->then(function ($channel) use ($message) {
                // Send a message to the channel confirming the channel was created
                $message->channel->sendMessage($this->setMessageOutput('Channel created: ' . $channel->name));
                $this->components->info('Channel created: ' . $channel->name);
            })
            ->catch(function ($e) use ($message) {
                // Send a message to the channel confirming the channel failed to be created
                $message->channel->sendMessage($this->setMessageOutput('Failed to create channel: ' . $e->getMessage()));
                $this->components->error('Failed to create channel: ' . $e->getMessage());
            });
    }

    private function handleAssignRole(mixed $message, mixed $discord): void
    {
        $usage = 'Usage: !assign-role <role-name> <user-mention>';

        $this->components->info('Received assign role request!');

        // Check if the message content contains 3 parts
        $parts = explode(' ', $message->content);
        if (count($parts) < 3) {
            $message->channel->sendMessage($this->setMessageOutput($usage));
            return;
        }

        // Extract the role name and user mention
        $roleName = $parts[1];
        $userMention = $parts[2];

        // Validate the user mention
        if (! str_starts_with($userMention, '<@') || ! str_ends_with($userMention, '>')) {
            $message->channel->sendMessage($this->setMessageOutput('Invalid user mention format.'));
            return;
        }

        // Extract the user ID from the mention
        $userId = trim($userMention, '<@!>');

        // Get the guild from the message
        $guild = $message->channel->guild;

        if (! $guild) {
            $message->channel->sendMessage($this->setMessageOutput('Server not found.'));
            return;
        }

        // Find the role in the guild
        $role = $guild->roles->find(function ($role) use ($roleName) {
            return strtolower($role->name) === strtolower($roleName);
        });

        if (! $role) {
            $message->channel->sendMessage($this->setMessageOutput("Role '{$roleName}' not found."));
            return;
        }

        // Find the member in the guild
        $member = $guild->members->get('id', $userId);

        if (! $member) {
            $message->channel->sendMessage($this->setMessageOutput('User not found in the server.'));
            return;
        }

        // Assign the role to the member
        $member->addRole($role->id)
            ->then(function () use ($message, $roleName, $member) {
                $message->channel->sendMessage($this->setMessageOutput("Successfully assigned role '{$roleName}' to {$member->username}."));
                $this->components->info("Assigned role '{$roleName}' to {$member->username}.");
            })
            ->catch(function ($e) use ($message, $roleName) {
                $message->channel->sendMessage($this->setMessageOutput("Failed to assign role '{$roleName}': {$e->getMessage()}"));
                $this->components->error("Failed to assign role '{$roleName}': {$e->getMessage()}");
            });
    }


    private function handlePing(mixed $message): void
    {
        $this->components->info('Received ping!');
        $message->channel->sendMessage($this->setMessageOutput('pong!'));
        $this->components->info('Sent pong!');
    }
}
