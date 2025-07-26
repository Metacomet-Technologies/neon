<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


// Helpers replaced by SDK
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;

final class ProcessCreatePollJob extends ProcessBaseJob
{
    private readonly string $question;
    private readonly array $options;

    // Retry logic handled by SDK

    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);

        // Parse the poll question and options in constructor
        [$this->question, $this->options] = $this->parseMessage();
    }

    // TODO: Add ability for emojis to be included in text
    /**
     * Handles the job execution.
     */
    protected function executeCommand(): void
    {
        // Check permissions using SDK
        $discord = new Discord;
        $member = $discord->guild($this->guildId)->member($this->discordUserId);

        if (! $member->canSendPolls()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to send polls in this server.');
            throw new Exception('User does not have permission to send polls in this server.', 403);
        }

        // Validate input: Must have a question and at least two options
        if (! $this->question || count($this->options) < 2 || count($this->options) > 10) {
            $this->sendUsageAndExample();
            if (count($this->options) > 10) {
                $discord->channel($this->channelId)->send('❌ Polls can have a maximum of 10 options.');
            }
            throw new Exception('Operation failed', 500);
        }
        try {
            // Discord instance already created above
            $channel = $discord->channel($this->channelId);


            // Create the poll using the SDK's sendPoll method
            $channel->sendPoll(
                $this->question,
                $this->options,
                duration: 24, // 24 hours
                allowMultiselect: false
            );

            // The SDK automatically handles the formatting, so we don't need to send a separate success message
            // The poll itself is the confirmation
        } catch (Exception $e) {
            $discord->channel($this->channelId)->send('❌ Failed to create the poll.');
            throw new Exception('Operation failed', 500);
        }
    }

    /**
     * Parses the message content for the poll question and options.
     */
    private function parseMessage(): array
    {
        // Normalize curly quotes to straight quotes
        $message = str_replace(['“', '”'], '"', $this->messageContent);

        preg_match_all('/"([^"]+)"/', $message, $matches);

        if (count($matches[1]) < 2) {
            return ['', []]; // Invalid input (needs at least a question and 2 options)
        }
        $question = array_shift($matches[1]);
        $options = $matches[1];

        return [$question, $options];
    }
}
