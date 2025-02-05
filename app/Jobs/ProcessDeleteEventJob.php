<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteEventJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'delete-event',
    // 'description' => 'Deletes a scheduled event.',
    // 'class' => \App\Jobs\ProcessDeleteEventJob::class,
    // 'usage' => 'Usage: !delete-event <event-id>',
    // 'example' => 'Example: !delete-event 123456789012345678',
    // 'is_active' => true,

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'delete-event')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage events
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanCreateEvents($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to delete events in this server.',
            ]);

            return;
        }

        // Parse the command message
        $eventId = $this->parseMessage($this->messageContent);

        if (! $eventId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Construct the delete API request
        $deleteUrl = $this->baseUrl . "/guilds/{$this->guildId}/scheduled-events/{$eventId}";

        // Make the delete request with retries
        $deleteResponse = retry(3, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
        }, 200);

        if ($deleteResponse->failed()) {
            Log::error("Failed to delete event '{$eventId}' in guild {$this->guildId}", [
                'response' => $deleteResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete event (ID: `{$eventId}`).",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Event Deleted!',
            'embed_description' => "**Event ID:** `{$eventId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
    }

    /**
     * Parses the message content for extracting the event ID.
     */
    private function parseMessage(string $message): ?string
    {
        // Remove invisible characters (zero-width spaces, control characters)
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message); // Removes control characters
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage)); // Normalize spaces

        // Use regex to extract the event ID
        preg_match('/^!delete-event\s+(\d{17,19})$/iu', $cleanedMessage, $matches);

        return $matches[1] ?? null;
    }
}
