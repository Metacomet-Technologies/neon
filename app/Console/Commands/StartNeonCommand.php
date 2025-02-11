<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\Discord\SendMessage;
use App\Jobs\NeonDispatchHandler;
use App\Jobs\ProcessGuildCommandJob;
use App\Jobs\ProcessScheduledMessageJob;
use App\Models\NativeCommand;
use App\Models\NeonCommand;
use Carbon\Carbon;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Exception;
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
                        // Special handling for scheduled messages
                        if ($commandSlug === '!scheduled-message') {
                            $this->handleScheduledMessage($discordUserId, $channelId, $guildId, $messageContent);

                            return;
                        }

                        NeonDispatchHandler::dispatch($discordUserId, $channelId, $guildId, $messageContent, $command);

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

    public function handleScheduledMessage(string $discordUserId, string $channelId, string $guildId, string $messageContent): void
    {
        $parts = explode(' ', trim($messageContent), 5); // Allow 5 parts: channel, date, time, and message

        // Extract target channel, date, time, and message
        $targetChannelMention = $parts[1] ?? null;
        $date = $parts[2] ?? null;
        $time = $parts[3] ?? null;
        $messageText = $parts[4] ?? null;

        // Extract channel ID from mention format <#channelID>
        if (preg_match('/<#(\d+)>/', $targetChannelMention, $channelMatches)) {
            $targetChannelId = $channelMatches[1];
        } else {
            SendMessage::sendMessage($channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid channel format. Please mention a valid channel.\n\n**Example:** `!scheduled-message #announcements 2025-02-07 18:48 Server maintenance Starting!`",
            ]);

            return;
        }

        // Rebuild full datetime string
        $dateTime = $date . ' ' . $time;

        // Debugging Information
        // dump('Scheduled Message Debug - Raw Input:', [
        //     'full_message' => $messageContent,
        //     'split_parts' => $parts,
        //     'target_channel' => $targetChannelId,
        //     'date' => $date,
        //     'time' => $time,
        //     'dateTime' => $dateTime,
        //     'messageText' => $messageText,
        // ]);

        try {
            // Strictly enforce date-time parsing in UTC
            $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime, 'UTC')->setTimezone('UTC');

            // Dump parsed time
            // dump('Parsed Date-Time:', [
            //     'input' => $dateTime,
            //     'parsed' => $scheduledTime->toDateTimeString(),
            //     'current_time' => Carbon::now('UTC')->toDateTimeString(),
            // ]);

            // Ensure time is in the future
            if ($scheduledTime->isPast()) {
                SendMessage::sendMessage($channelId, [
                    'is_embed' => false,
                    'response' => "❌ The scheduled time **must be in the future (UTC)**.\n\n**Your Input:** `{$dateTime}`\n**Parsed Time:** `{$scheduledTime->toDateTimeString()} UTC`\n**Current Time:** `" . Carbon::now('UTC')->toDateTimeString() . '`',
                ]);

                return;
            }
        } catch (Exception $e) {
            // dump('Scheduled Message Parsing Error:', [
            //     'error_message' => $e->getMessage(),
            //     'input_time' => $dateTime,
            // ]);

            SendMessage::sendMessage($channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid date-time format. Use: `YYYY-MM-DD HH:MM` (UTC)\n\n**Example:** `!scheduled-message #announcements 2025-02-07 18:48 Server maintenance Starting!`",
            ]);

            return;
        }

        // Validate message content
        if (! $messageText) {
            SendMessage::sendMessage($channelId, [
                'is_embed' => false,
                'response' => "ℹ️ **Scheduled Message Help**\nSchedules a message to be sent later in a specific channel.\n\n**Usage:** `!scheduled-message <#channel> <YYYY-MM-DD HH:MM> <message>`\n\n**Example:** `!scheduled-message #announcements 2025-02-07 18:48 Server maintenance Starting!`",
            ]);

            return;
        }

        // Schedule the job
        ProcessScheduledMessageJob::dispatch($targetChannelId, $messageText)->delay($scheduledTime);

        SendMessage::sendMessage($channelId, [
            'is_embed' => false,
            'response' => "✅ Your message has been scheduled for **<#{$targetChannelId}> at {$scheduledTime->toDateTimeString()} UTC**.",
        ]);
    }
}
