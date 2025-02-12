<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessCreatePollJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private string $question;
    private array $options = [];

    private int $maxRetries = 3;
    private int $retryDelay = 2000;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    //TODO: Add ability for emojis to be included in text
    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanSendPolls($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to send polls in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to send polls in this server.',
                statusCode: 403,
            );

            return;
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

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid poll format. Poll did not have a question and/or at least two options.',
                details: [
                    'question' => $this->question ?? '',
                    'options' => $this->options ?? [],
                ],
                statusCode: 400,
            );

            return;
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
            $this->updateNativeCommandRequestFailed(
                status: 'discord-api-error',
                message: 'Failed to ban user.',
                statusCode: $apiResponse->status(),
                details: $apiResponse->json(),
            );
        }
        $this->updateNativeCommandRequestComplete();
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
