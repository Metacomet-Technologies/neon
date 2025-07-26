<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Support\Facades\Http;

final class ProcessCreatePollJob extends ProcessBaseJob
{
    private string $question;
    private array $options = [];

    private int $maxRetries = 3;
    private int $retryDelay = 2000;

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
    }

    // TODO: Add ability for emojis to be included in text
    /**
     * Handles the job execution.
     */
    protected function executeCommand(): void
    {
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanSendPolls($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to send polls in this server.',
            ]);
            throw new Exception('User does not have permission to send polls in this server.', 403);
        }
        // Parse the poll question and options
        [$this->question, $this->options] = $this->parseMessage();

        // Validate input: Must have a question and at least two options
        if (! $this->question || count($this->options) < 2 || count($this->options) > 10) {
            $this->sendUsageAndExample();
            if (count($this->options) > 10) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => '❌ Polls can have a maximum of 10 options.',
                ]);
            }
            throw new Exception('Operation failed', 500);
        }
        // Construct the poll payload
        $pollPayload = [
            'content' => '**📊 Poll Created! Click below to vote!**',
            'poll' => [
                'question' => [
                    'text' => $this->question,
                ],
                'answers' => array_map(fn ($index, $option) => [
                    'poll_media' => [
                        'text' => (string) $option,
                        // 'emoji' => [
                        //     'name' => $defaultEmojis[$index] ?? '✅',
                        // ],
                    ],
                ], array_keys($this->options), $this->options),
                'duration' => 24, // Default to 24 hours
                'allow_multiselect' => false,
                'layout_type' => 1,
            ],
        ];

        // Send the poll message
        $pollUrl = "{$this->baseUrl}/channels/{$this->channelId}/messages";
        $apiResponse = retry($this->maxRetries, function () use ($pollUrl, $pollPayload) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->post($pollUrl, $pollPayload);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create the poll.',
            ]);
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
