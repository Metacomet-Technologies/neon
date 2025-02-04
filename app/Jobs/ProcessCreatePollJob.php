<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ProcessCreatePollJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages dynamically fetched from the database.
     */
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'poll',
    // 'description' => 'Creates a poll with multiple voting options.',
    // 'class' => \App\Jobs\ProcessCreatePollJob::class,
    // 'usage' => 'Usage: !poll "Question" "Option 1" "Option 2" "Option 3"',
    // 'example' => 'Example: !poll "What should we play?" "Minecraft" "Valorant" "Overwatch"',
    // 'is_active' => true,

    public string $baseUrl;

    private string $question;
    private array $options = [];

    private int $maxRetries = 3;
    private int $retryDelay = 2000;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');

        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'poll')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
    }
//TODO: Add ability for emojis to be included in text
    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Parse the poll question and options
        [$this->question, $this->options] = $this->parseMessage($this->messageContent);

        // Validate input: Must have a question and at least two options
        if (! $this->question || count($this->options) < 2) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanSendPolls($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to send polls in this server.',
            ]);

            return;
        }

        // Ensure we have at least 2 options and at most 10
        if (count($this->options) > 10) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Polls can have a maximum of 10 options.',
            ]);

            return;
        }

        // List of default number emojis for poll choices
        // $defaultEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];

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
        $response = retry($this->maxRetries, function () use ($pollUrl, $pollPayload) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->post($pollUrl, $pollPayload);
        }, $this->retryDelay);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create the poll.',
            ]);
        }
    }

    /**
     * Parses the message content for the poll question and options.
     */
    private function parseMessage(string $message): array
    {
        // Normalize curly quotes to straight quotes
        $message = str_replace(['“', '”'], '"', $message);

        preg_match_all('/"([^"]+)"/', $message, $matches);

        if (count($matches[1]) < 2) {
            return ['', []]; // Invalid input (needs at least a question and 2 options)
        }

        $question = array_shift($matches[1]);
        $options = $matches[1];

        return [$question, $options];
    }
}
